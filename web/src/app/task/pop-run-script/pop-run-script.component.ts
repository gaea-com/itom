import {Component, ElementRef, Inject, OnInit, ViewChild} from '@angular/core';
import {ApiService} from "../../_Service/api.service";
import {ToolsService} from "../../_Service/tools.service";
import {ProjectListComponent} from "../../project/project-list/project-list.component";
import {MAT_DIALOG_DATA, MatAutocompleteSelectedEvent, MatDialogRef, MatSnackBar} from "@angular/material";
import {FormControl, FormGroup} from "@angular/forms";
import {InstanceData, InstanceModule} from "../../_Module/InstanceModule";
import {Observable} from "rxjs/Observable";
import {map, startWith} from "rxjs/operators";
import {COMMA, ENTER} from "@angular/cdk/keycodes";
import {HttpParams} from "@angular/common/http";
import {ContainerData, ContainerModule} from "../../_Module/ContainerModule";
import {CustomerGroupData, CustomerGroupModule} from "../../_Module/CustomerGroupModule";

@Component({
  selector: 'app-pop-run-script',
  templateUrl: './pop-run-script.component.html',
  styleUrls: ['./pop-run-script.component.sass']
})
export class PopRunScriptComponent implements OnInit {
  scriptName:string;
  script:string;
  id:number;
  pid:string;
  type:number;
  myForm:FormGroup;
  removable = true;
  selectable = true;

  @ViewChild('instanceInput', {static: true}) instanceInput:ElementRef;
  instanceOption:InstanceModule[];
  instanceOptionStatus:Observable<any[]>;
  instanceArr:string[] = [];
  instanceSelected:any = {};
  instanceGroupOption:any[] = [];

  @ViewChild('dockerInput', {static: true}) dockerInput:ElementRef;
  dockerOption:ContainerModule[];
  dockerOptionStatus:Observable<any[]>;
  dockerSelected:any = {};
  dockerArr:string[] = [];
  dockerGroupOption:any[] = [];

  constructor(
    private apiServer: ApiService,
    public tools:ToolsService,
    public dialogRef: MatDialogRef<ProjectListComponent>,
    @Inject(MAT_DIALOG_DATA) public data: any,
    public snackBar: MatSnackBar
  ) { }

  ngOnInit() {
    this.scriptName = this.data.name;
    this.script = this.data.script;
    this.type = this.data.type;
    this.id = this.data.id;
    this.pid = this.data.pid;

    if(this.type == 200){
      this.getInstanceOption();
      this.getCustomerGroupOption(100);
    }else{
      this.getDockerOption();
      this.getCustomerGroupOption(200);
    }

    this.myForm = new FormGroup({
      id: new FormControl(this.id),
      targetType: new FormControl(''),
      container: new FormControl(''),
      containerGroup: new FormControl(''),
      instance: new FormControl(''),
      instanceGroup: new FormControl('')
    })
  }

  remove(item, type):void{
    let chips = (type == 'ins') ? this.instanceArr : this.dockerArr;
    let idx = chips.indexOf(item);

    if(idx >= 0){
      chips.splice(idx, 1);
    }
  }

  link(type){
    this.dialogRef.close(type);
  }

  selectedInstance(event: MatAutocompleteSelectedEvent){
    let val = event.option.value;
    this.insertInstanceChipArr(val);
  }

  selectedDocker(event: MatAutocompleteSelectedEvent){
    let val = event.option.value;
    this.insertDockerChipArr(val);
  }

  submitForm(){
    let T = this.checkForm();

    if(T){
      let formData = this.formatFormData();
      if(this.type == 200) {
        this.apiServer.runScriptToInstance(formData, this.pid).subscribe((res) => {
          if(res['status'] == 200){
            this.tools.StatusSuccess(res, '命令提交成功')
            this.dialogRef.close();
          }else{
            this.tools.StatusError(res);
          }
        }, (error) => {
          this.tools.ServerError(error);
        })
      }else {
        this.apiServer.runScriptToDocker(formData, this.pid).subscribe((res) => {
          if(res['status'] == 200){
            this.tools.StatusSuccess(res, '命令提交成功');
            this.dialogRef.close();
          }else{
            this.tools.StatusError(res);
          }
        }, (error) => {
          this.tools.ServerError(error);
        })
      }
    }
  }

  private insertInstanceChipArr(value){
    let name = value.name;
    if(!this.instanceSelected[name]){
      this.instanceSelected[name] = 1;
      this.instanceArr.push(value);
    }

    this.instanceInput.nativeElement.value = '';
  }

  private insertDockerChipArr(value){
    let name = value.name;
    if(!this.dockerSelected[name]){
      this.dockerSelected[name] = 1;
      this.dockerArr.push(value);
    }
    this.dockerInput.nativeElement.value = '';
  }

  private getDockerOption(){
    let formData = new HttpParams();
    formData = formData.set('project_id', this.pid)
    this.apiServer.getContainer(formData, this.pid).subscribe((res) => {
      if(res['status'] == 200){
        let option = new ContainerData<ContainerModule>(res['data']);
        this.dockerOption = option.option;
        this.dockerOptionStatus = this.myForm.get('container').valueChanges
          .pipe(
            startWith(''),
            map((value:string | null) => (value && typeof(value) == 'string') ? this._Filter(value, 'docker') : this.dockerOption)
          )
      }else{
        this.tools.StatusError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }

  private getInstanceOption(){
    let formData = new HttpParams();
    formData = formData.set('pid', this.pid)
    this.apiServer.getTopologyList(formData, this.pid).subscribe((res) => {
      if(res['status'] == 200){
        let option = new InstanceData<InstanceModule>(res['data']);
        this.instanceOption = option.option;
        this.instanceOptionStatus = this.myForm.get('instance').valueChanges
          .pipe(
            startWith(''),
            map((value:string | null) => (value && typeof(value) == 'string') ? this._Filter(value, 'ins') : this.instanceOption)
          )
      }else{
        this.tools.StatusError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }

  private getCustomerGroupOption(type){
    let formData = new HttpParams();
    formData = formData.set('pid', this.pid);
    formData = formData.set('type', type);
    this.apiServer.getCustomerGroup(formData, this.pid).subscribe((res) => {
      if(res['status'] == 200){
        let option = new CustomerGroupData<CustomerGroupModule>(res['data']);
        if(type == 200){
          this.dockerGroupOption = option.option;
        }else{
          this.instanceGroupOption = option.option;
        }
      }else{
        this.tools.StatusError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }

  private _Filter(value:string, type:string):InstanceModule[]{
    let Arr:any[] = (type == 'ins') ? this.instanceOption : this.dockerOption;
    return  Arr.filter(option => option.name.indexOf(value) >= 0)
  }

  private formatFormData(){
    let formData = new HttpParams();
    formData = formData.set('type', 'script');
    formData = formData.set('pid', this.pid);
    formData = formData.set('cmd', this.data.script);
    let type = parseInt(this.myForm.get('targetType').value);

    switch (type) {
      case 1:  //实例
        let request = [];
        this.instanceArr.forEach(prop => {
          let item = {};
          item['id'] = prop['id'];
          item['cloud_type'] = 'gaea';
          item['ip'] = prop['internal_ip'];
          request.push(item);
        });
        formData = formData.set('request', JSON.stringify(request));
        break;
      case 2:  //自定义实例组
        formData = formData.set('group_id', this.myForm.get('instanceGroup').value);
        break;
      case 3:  //容器
        let ids = [];
        this.dockerArr.forEach(prop => {
          ids.push(prop['id']);
        });
        formData = formData.set('id', JSON.stringify(ids));
        break;
      default:  //自定义容器组
        formData = formData.set('group_id', this.myForm.get('containerGroup').value);
        break
    }
    return formData;
  }

  private checkForm(){
    let T:boolean = true;
    let type = parseInt(this.myForm.get('targetType').value);

    switch (type){
      case 1:  //实例
        if(this.instanceArr.length == 0){
          T = false;
          this.snackBar.open('运行实例不能为空', '',{
            duration: 1000,
            panelClass: ['error-toaster']
          });
        }
        break;
      case 2: //自定义实例组
        if(this.myForm.get('instanceGroup').value == ''){
          T = false;
          this.snackBar.open('自定义实例组不能为空', '',{
            duration: 1000,
            panelClass: ['error-toaster']
          });
        }
        break;
      case 3: //容器
        if(this.dockerArr.length == 0){
          T = false;
          this.snackBar.open('运行容器不能为空', '',{
            duration: 1000,
            panelClass: ['error-toaster']
          });
        }
        break;
      default: //自定义容器组
        if(this.myForm.get('containerGroup').value == ''){
          T = false;
          this.snackBar.open('自定义容器组不能为空', '',{
            duration: 1000,
            panelClass: ['error-toaster']
          });
        }
        break;
    }
    return T
  }
}
