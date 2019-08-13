import {Component, Inject, OnInit, ViewChild} from '@angular/core';
import {ActivatedRoute, Router} from "@angular/router";
import {FormArray, FormBuilder, FormControl, FormGroup, Validators} from "@angular/forms";
import {ToolsService} from "../_Service/tools.service";
import {MAT_DIALOG_DATA, MatAccordion, MatDialogRef, MatSnackBar} from "@angular/material";
import {HttpParams} from "@angular/common/http";
import {ApiService} from "../_Service/api.service";
import {EnvData, EnvModule} from "../_Module/EnvModule";
import {ImageData, ImageModule} from "../_Module/ImageModule";
import {Location} from "@angular/common";

@Component({
  selector: 'app-env',
  templateUrl: './env.component.html',
  styleUrls: ['./env.component.sass']
})
export class EnvComponent implements OnInit {
  pid:string;
  sid:string;
  cid:string;
  myForm:FormGroup;
  isShowForm:boolean = false;
  iconStatus:boolean = true;
  imageArr:ImageModule[];

  @ViewChild('myaccordion', {static:true}) myPanel: MatAccordion;
  get configListControl(){
    return this.myForm.get('configList') as FormArray;
  }

  constructor(
    private route: ActivatedRoute,
    private tools: ToolsService,
    public snackBar: MatSnackBar,
    private fb:FormBuilder,
    private location: Location,
    private router: Router,
    private apiService: ApiService) {
    this.route.params.subscribe(params => {
      if(params.params){
        this.tools.parseParams(params.params, (obj) => {
          this.pid = obj['pid'];
          this.sid = obj['sid'];
          this.cid = obj['cid'];

          this.getEnvInitData();
        })
      }
    });
  }

  ngOnInit() {
  }

  toggleGroup(event){
    let status = event.value;
    if(status){
      this.myPanel.openAll();
      this.iconStatus = true;
    }else{
      this.myPanel.closeAll();
      this.iconStatus = false;
    }
  }

  addListItem(type, imageIdx, prop?){
    console.log(prop);
    let imageCtl = this.configListControl.get(imageIdx.toString()) as FormGroup;
    let itemCtl = imageCtl.get(type) as FormArray;
    let item:FormGroup;
    if(type == 'envList'){
      item = new FormGroup({
        Key: new FormControl((prop && prop.Key) ? prop.Key : ''),
        Val: new FormControl((prop && prop.Value) ? prop.Value : ''),
      });
    }else{
      item = new FormGroup({
        Key: new FormControl((prop && prop.Key) ? prop.Key : ''),
        Val: new FormControl((prop && prop.Value) ? prop.Value : ''),
      });
    }

    itemCtl.controls.push(item);

    return false;
  }

  removeListItem(i, itemCtl:FormArray){
    if(i || itemCtl.length > 1){
      itemCtl.removeAt(i);
    }else{
      this.snackBar.open('最后一项无法删除', '',{
        duration: 1000,
        panelClass: ['error-toaster']
      });
    }
    return false;
  }

  submitForm() {
    this.checkForm();

    if(this.myForm.status == 'VALID'){
      this.submitItemForm(0);
    }else{
      this.snackBar.open('表单验证未通过', '',{
        duration: 1000,
        panelClass: ['error-toaster']
      });
    }
  }

  loadImage(){
    let item = {}
    item['project_id'] = this.pid;
    item['instance_id'] = this.sid;
    item['cloud_type'] = 'gaea';

    let formData = new HttpParams();
    formData = formData.set('ischeck', '1');
    formData = formData.set('request', JSON.stringify([item]));

    this.apiService.loadImage(formData, this.pid).subscribe((res) => {
      if(res['status'] == 200){
        this.tools.StatusSuccess(res, '任务已经提交，请耐心等待');
      }else{
        this.tools.StatusError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    });
  }

  private submitItemForm(idx){
    if(idx == this.configListControl.length){
      this.snackBar.open('所有镜像配置已全部提交完毕！', '',{
        duration: 1000,
        panelClass: ['success-toaster']
      });
      this.location.back();
      return false;
    }

    let itemCtl = this.configListControl.get(idx.toString()) as FormGroup;
    let envCtl = itemCtl.get('envList') as FormArray;
    let volCtl = itemCtl.get('volumeList') as FormArray;
    let env:any = this.getItemFormList(envCtl);
    let volume:any = this.getItemFormList(volCtl);

    if(env['option'].length == 0 && volume['option'].length == 0){
      this.snackBar.open('环境变量和数据卷不能同时为空', '',{
        duration: 1000,
        panelClass: ['error-toaster']
      });
    }else{
      let formData = new HttpParams();
      formData = formData.set('server_id', this.sid);
      formData = formData.set('project_id', this.pid);
      formData = formData.set('image_name', itemCtl.get('imageName').value);
      formData = formData.set('name', itemCtl.get('dockerName').value);   //compose id
      formData = formData.set('descr', itemCtl.get('dockerDescription').value);
      formData = formData.set('num', idx.toString());
      if(env['option'].length > 0){
        formData = formData.set('env', JSON.stringify(env['list']));
      }
      if(volume['option'].length > 0){
        formData = formData.set('data', JSON.stringify(volume['list']));
      }

      this.apiService.updateEnvItem(formData, this.pid).subscribe((res) => {
        console.log(res);
        if(res['status'] == 200){
          if(idx < this.configListControl.controls.length){
            this.tools.StatusSuccess(res, '镜像'+ this.imageArr[idx]['name']+'配置，已提交成功！');
            idx++;
            this.submitItemForm(idx);
          }else{
            this.snackBar.open('所有镜像配置已全部提交完毕！', '',{
              duration: 1000,
              panelClass: ['success-toaster']
            });
          }
        }else{
          this.tools.StatusError(res);
        }
      }, (error) => {
        this.tools.ServerError(error);
      })
    }

  }

  private getItemFormList(Arr){
    let result = {};
    result['option'] = [];
    result['list'] = {};
    Arr.controls.forEach(field => {
      let Key = field.get('Key');
      let Val = field.get('Val');
      if(Key.value){
        result['list'][Key.value] = Val.value;
        result['option'].push('1');
      }
    });
    return result;
  }

  private getEnvInitData(){
    let formData = new HttpParams();
    formData = formData.set('server_id', this.sid);
    formData = formData.set('type', 'env');
    formData = formData.set('project_id', this.pid);
    this.apiService.getEnvImageList(formData, this.pid).subscribe((res) => {
        if(res['status'] == 200){
        let option = new ImageData<ImageModule>(res['data']);
        this.imageArr = option.option;
        this.getEnvItemList(option.option);
        // if(this.imageArr.length > 0){
        // }else{
        //   this.snackBar.open('还未拉去镜像，请拉取！', '', {
        //     duration: 3000,
        //     panelClass: ['error-toaster']
        //   });
        //   this.router.navigate(['../../instance'], {relativeTo: this.route});
        // }
      }else{
        this.tools.StatusError(res)
      }
    },(error) => {
      this.tools.ServerError(error);
    })
  }

  private getEnvItemList(Arr){
    let ids = Arr.map(item => item.name);
    let formData = new HttpParams();
    formData = formData.set('server_id', this.sid);
    formData = formData.set('project_id', this.pid);
    formData = formData.set('image_name', JSON.stringify(ids));

    this.apiService.getEnvItem(formData, this.pid).subscribe((res) => {
      if(res['status'] == 200){
        let option = new EnvData<EnvModule>(res['data']);
        this.initForm(Arr, option.option);
      }else{
        this.tools.StatusError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }

  private initForm(imageArr, imageItemArr){
    let configListArr = this.getConfigArrList(imageArr, imageItemArr);

    this.myForm = this.fb.group({
      configList: this.fb.array(configListArr)
    });

    this.isShowForm = true;
  }

  private getConfigArrList(imageArr, imageDefaultObj){
    let result = [];
    imageArr.forEach(field => {
      let image = imageDefaultObj[field.name];
      let envArr = [];
      let volumeArr = [];
      if(image){
        image.envList.forEach(prop => {
          envArr.push(this.getDefaultArrList(prop));
        });
        image.volumeList.forEach(prop => {
          volumeArr.push(this.getDefaultArrList(prop));
        });
      }else{
        envArr.push(this.getDefaultArrList());
        volumeArr.push(this.getDefaultArrList());
      }

      let item = {
        imageName: new FormControl(field.name),
        dockerName: new FormControl((image) ? image.docker_name : '', Validators.required), //image.docker_name
        dockerDescription: new FormControl((image) ? image.docker_description : '', Validators.required), //image.docker_description
        envList: new FormArray(envArr),
        volumeList: new FormArray(volumeArr)
      }
      result.push(new FormGroup(item));
    });
    return result;
  }

  private getDefaultArrList(prop?){
    let item = new FormGroup({
      Key: new FormControl((prop && prop.Key) ? prop.Key : ''),
      Val: new FormControl((prop && prop.Value) ? prop.Value : ''),
    });
    return item
  }

  private setItem(imageArr, imageItemArr, callback){
    console.log(imageItemArr);
    imageArr.forEach((field, imageIdx) => {
      let image = imageItemArr[field.name];
      if(image){
        if(image.envList && image.envList.length > 0){
          image.envList.forEach(prop => {
            this.addListItem('envList', imageIdx, prop);
          });
        }

        if(image.volumeList && image.volumeList.length > 0){
          image.volumeList.forEach(prop => {
            this.addListItem('volumeList', imageIdx, prop);
          });
        }
      }

    });

    if(callback){
      callback();
    }
  }

  private checkForm(){
    if(this.configListControl.controls.length > 0){
      this.configListControl.controls.forEach((field:FormGroup) => {
        let dockerNameCtl = field.get('dockerName');
        let dockerDesCtrl = field.get('dockerDescription');

        dockerNameCtl.markAsTouched();
        dockerNameCtl.updateValueAndValidity();
        dockerDesCtrl.markAsTouched();
        dockerDesCtrl.updateValueAndValidity();
      });
      this.configListControl.updateValueAndValidity();
    }
  }
}
