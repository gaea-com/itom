import {Component, OnInit} from '@angular/core';
import {Location} from "@angular/common";
import {ActivatedRoute} from "@angular/router";
import {ToolsService} from "../../_Service/tools.service";
import {ApiService} from "../../_Service/api.service";
import {MatSnackBar} from "@angular/material";
import {FormControl, FormGroup, Validators} from "@angular/forms";
import {ContainerData, ContainerModule} from "../../_Module/ContainerModule";
import {HttpParams} from "@angular/common/http";
import {GroupData, GroupModule} from "../../_Module/GroupModule";
import {CustomerGroupData, CustomerGroupModule} from "../../_Module/CustomerGroupModule";

@Component({
  selector: 'app-create-customer-group',
  templateUrl: './create-customer-group.component.html',
  styleUrls: ['./create-customer-group.component.sass']
})
export class CreateCustomerGroupComponent implements OnInit {
  type:string;
  title:string;
  pid:string;
  id:string;
  myForm:FormGroup;
  isShowForm:boolean = false;
  fromOption:any[] = [];
  toOption:any[] = [];
  groupOption:GroupModule[];

  constructor(private tools:ToolsService,
              private apiService:ApiService,
              public snackBar: MatSnackBar,
              private location: Location,
              private route: ActivatedRoute) { }

  ngOnInit() {
    this.route.parent.params.subscribe(params => {
      if(params.params){
        this.tools.parseParams(params.params, (obj) => {
          this.pid = obj['pid'];
        })
      }
    });

    this.route.params.subscribe(params => {
      if(params.params){
        this.tools.parseParams(params.params, (obj) => {
          this.type = obj['type'];
          this.title = (parseInt(this.type) == 100) ? '自定义实例组' : '自定义容器组';
          this.id = obj['id'] || null;
          this.getOption();
        })
      }
    });
  }

  submitForm(){

    let T = this.checkForm();
    if(this.myForm.status == 'VALID' && T){
      let formData = this.getFormData();
      if(this.id){
        formData = formData.set('id', this.id);
        this.apiService.updateCustomerGroup(formData, this.pid).subscribe((res) => {
          if(res['status'] == 200){
            this.tools.StatusSuccess(res, '修改成功');
            this.location.back();
          }else{
            this.tools.StatusError(res);
          }
        }, (error) => {
          this.tools.ServerError(error);
        });
      }else{
        this.apiService.createCustomerGroup(formData, this.pid).subscribe((res) => {
          if(res['status'] == 200){
            this.tools.StatusSuccess(res, '创建成功');
            this.location.back();
          }else{
            this.tools.StatusError(res);
          }
        }, (error) => {
          this.tools.ServerError(error);
        })
      }
    }
  }

  private checkForm(){
    let T = true;
    let nameCtl = this.myForm.get('name');
    let desCtl = this.myForm.get('description');
    let resultCtl = this.myForm.get('result');

    nameCtl.markAsTouched();
    nameCtl.updateValueAndValidity();
    desCtl.markAsTouched();
    desCtl.updateValueAndValidity();

    if(resultCtl.value == '' || resultCtl.value.length == 0){
      T = false;
      this.snackBar.open('请选择容器或者实例', '', {
        duration: 1000,
        panelClass: ['error-toaster']
      });
    }

    return T;
  }

  private getDetail(){
    if(this.id){
      let formData = new HttpParams();
      formData = formData.set('pid', this.pid);
      formData = formData.set('id', this.id);

      this.apiService.getCustomerGroup(formData, this.pid).subscribe((res) => {
        if(res['status'] == 200){
          let option = new CustomerGroupData<CustomerGroupModule>(res['data']);
          this.initForm(option.option[0]);
        }else{
          this.tools.StatusError(res);
        }
      }, (error) => {
        this.tools.ServerError(error);
      })
    }else{
      this.initForm(null);
    }
  }

  private initForm(data?:any){

    this.myForm = new FormGroup({
      name: new FormControl((data && data.name) ? data.name : '', Validators.required),
      description: new FormControl((data && data.description) ? data.description : '',Validators.required),
      result: new FormControl((data && data.server) ? data.server : '')
    });

    if(data){
      this.toOption = data.server;
    }
    this.isShowForm = true;
  }

  private getOption(){
    if(parseInt(this.type) == 100){
      this.groupOption = [];
      this.apiService.getGroupList(this.pid).subscribe((res) => {
        if(res['status'] == 200){
          let option = new GroupData<GroupModule>(res['data']);
          let idsArr = option.ids;
          let groupList = option.option;
          idsArr.forEach(id => {
            this.groupOption.push(groupList[id]);
          });
          this.getDetail();
        }else{
          this.tools.StatusError(res);
        }
      }, (error) => {
        this.tools.ServerError(error);
      })
    }else{
      let formData = new HttpParams();
      formData = formData.set('project_id', this.pid);
      this.apiService.getContainer(formData, this.pid).subscribe((res) => {
        if(res['status'] == 200){
          let option = new ContainerData<ContainerModule>(res['data']);
          this.fromOption = option.option;
          this.getDetail();

        }else{
          this.tools.StatusError(res);
        }
      }, (error) => {
        this.tools.ServerError(error);
      })
    }
  }

  private getFormData(){
    let resultCtl = this.myForm.get('result');
    let formData = new HttpParams();
    formData = formData.set('pid', this.pid);
    formData = formData.set('name', this.myForm.get('name').value);
    formData = formData.set('description', this.myForm.get('description').value);
    formData = formData.set('group_type', this.type);
    let server = [];
    if(parseInt(this.type) == 100){  //实例
      resultCtl.value.forEach(prop => {
        let item = {};
        item['type'] = 'gaea';
        item['server_name'] = prop['name'];
        item['server_id'] = prop['id'];
        server.push(item);
      });
    }else{   //容器
      resultCtl.value.forEach(prop => {
        let item = {};
        item['type'] = 'gaea';
        item['docker_id'] = prop['id'];
        item['server_id'] = prop['serverId'];
        server.push(item);
      });
    }
    formData = formData.set('server', JSON.stringify(server));
    return formData;
  }
}
