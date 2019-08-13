import {Component, Input, OnInit} from '@angular/core';
import {FormGroup} from "@angular/forms";
import {ScriptData, ScriptModule} from "../../_Module/ScriptModule";
import {HttpParams} from "@angular/common/http";
import {ToolsService} from "../../_Service/tools.service";
import {ApiService} from "../../_Service/api.service";
import {MatSnackBar} from "@angular/material";
import {GroupData, GroupModule} from "../../_Module/GroupModule";
import {InstanceData, InstanceModule} from "../../_Module/InstanceModule";
import {ContainerData, ContainerModule} from "../../_Module/ContainerModule";
import {Subscription} from "rxjs/Subscription";
import {OvserveFileService} from "../../_Service/ovserve-file.service";

@Component({
  selector: 'app-task-item',
  templateUrl: './task-item.component.html',
  styleUrls: ['./task-item.component.sass']
})
export class TaskItemComponent implements OnInit {
  @Input('type') type:number;
  @Input('myForm') myForm:FormGroup;
  @Input('pid') pid:string;
  @Input('defaultValue') defaultValue:any;
  @Input('status') status:string;
  scriptOption:ScriptModule[] = [];
  action:string;
  private subscription: Subscription;
  fromOption:any[] = [];
  toOption:any[] = [];
  isShowForm:boolean = false;

  constructor(private tools:ToolsService,
              private apiService:ApiService,
              private _setDefaultValue: OvserveFileService,
              public snackBar: MatSnackBar) {
    this.subscription = this._setDefaultValue.taskValue$.subscribe((res) => {
      this.setDefaultValue(res);
    });
  }

  ngOnInit() {
    this.action = (this.type == 100) ? '实例' : '容器';
    this.getScriptList();
  }

  selectItemType(event){
    let val = event.value;
    if(val){
      let resultCtl = this.myForm.get('result');
      resultCtl.reset();

      if(val == 1 || val == 2){
        this.isShowForm = false;
        this.getDefaultOption(val);
      }
    }
  }

  private getScriptList(){
    let type = (this.type == 100) ? '200' : '100';
    let formData = new HttpParams();
    formData = formData.set('pid', this.pid);
    formData = formData.set('type', type);
    this.apiService.getScript(formData, this.pid).subscribe((res) => {
      if(res['status'] == 200){
        let option = new ScriptData<ScriptModule>(res['data']);
        this.scriptOption = option.option;

        if(this.defaultValue){
          this.setDefaultValue(this.defaultValue);
        }
      }else{
        this.tools.ServerError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }

  private setDefaultValue(defaultValue){
    let type = defaultValue.itemTypeCode || defaultValue.itemType;
    if(defaultValue.id){
      this.scriptOption.forEach(item => {
        if(item.id == this.defaultValue.id){
          this.myForm.get('script').setValue(item);
        }
      });
    }

    if(defaultValue.itemTypeCode){
      this.myForm.get('itemType').setValue(this.defaultValue.itemTypeCode);
    }

    if(parseInt(type) == 1 || parseInt(type) == 2){
      this.myForm.get('itemType').setValue(type);
      this.toOption = this.defaultValue.group;
      this.myForm.get('result').setValue(this.defaultValue.group);
      this.getDefaultOption(type, this.defaultValue.group);
    }
  }

  private getDefaultOption(val, defaultValue?){
    this.fromOption = [];
    if(val == 1){
      this.apiService.getGroupList(this.pid).subscribe((res) => {
        if(res['status'] == 200){
          let option = new GroupData<GroupModule>(res['data']);
          option.ids.forEach(id => {
            this.fromOption.push(option.option[id]);
          });
          this.toOption = (defaultValue) ? defaultValue : [];
          this.isShowForm = true;
        }else{
          this.tools.StatusError(res);
        }
      }, (error) => {
        this.tools.ServerError(error);
      })
    }else{
      if(this.type == 100){
        let formData = new HttpParams();
        formData = formData.set('pid', this.pid);
        this.apiService.getTopologyList(formData, this.pid).subscribe((res) => {
          if(res['status'] == 200){
            let option = new InstanceData<InstanceModule>(res['data']);
            this.fromOption = option.option;
            this.toOption = (defaultValue) ? defaultValue : [];
            this.isShowForm = true;
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
            this.toOption = [];
            this.isShowForm = true;
          }else{
            this.tools.StatusError(res);
          }
        }, (error) => {
          this.tools.ServerError(error);
        })
      }
    }

  }

}
