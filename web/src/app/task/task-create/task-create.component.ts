import {Component, Input, OnInit, ViewChild} from '@angular/core';
import {FormControl, FormGroup, Validators} from "@angular/forms";
import {ScriptData, ScriptModule} from "../../_Module/ScriptModule";
import {ActivatedRoute} from "@angular/router";
import {ToolsService} from "../../_Service/tools.service";
import {ApiService} from "../../_Service/api.service";
import {HttpParams} from "@angular/common/http";
import {MatSnackBar, MatStepper} from "@angular/material";
import {ScriptListModule, TaskData, TaskDetailData, TaskDetailModule, TaskModule} from "../../_Module/TaskListModule";
import {CdkDragDrop, moveItemInArray} from "@angular/cdk/drag-drop";
import {Location} from "@angular/common";
import {OvserveFileService} from "../../_Service/ovserve-file.service";

@Component({
  selector: 'app-task-create',
  templateUrl: './task-create.component.html',
  styleUrls: ['./task-create.component.sass']
})
export class TaskCreateComponent implements OnInit {
  pid:string;
  id:string;
  isShowForm:boolean = false;
  firstFormGroup:FormGroup;
  secondFormGroup:FormGroup;
  scriptList:ScriptListModule[] = [];
  itemDefaultValue:any;
  status:string;

  @ViewChild('stepper', {static: true}) stepper: MatStepper;
  constructor(private tools:ToolsService,
              private apiService:ApiService,
              public snackBar: MatSnackBar,
              private location: Location,
              private setDefaultValue:OvserveFileService,
              private route: ActivatedRoute) {
    this.getDetail();
  }

  get typeControl(){
    return this.secondFormGroup.get('type');
  }

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
          this.id = obj['id'] || null;
          this.status = (this.id) ? 'edit' : 'create';
          this.getDetail();
        })
      }else{
        this.getDetail();
      }
    });
  }

  select(event){
    let val = event.value;

    if(val){
      let scriptCtl = this.secondFormGroup.get('script');
      scriptCtl.reset();
      let itemTypeCtl = this.secondFormGroup.get('itemType');
      itemTypeCtl.reset();
      let resultCtl = this.secondFormGroup.get('result');
      resultCtl.reset();
    }
  }

  addScript(event:MatStepper){
    let T = true;
    let idxCtl = this.secondFormGroup.get('idx');
    let typeCtl = this.secondFormGroup.get('type');
    let scriptCtl = this.secondFormGroup.get('script');
    let itemTypeCtl = this.secondFormGroup.get('itemType');
    let resultCtl = this.secondFormGroup.get('result');

    scriptCtl.markAsTouched();
    scriptCtl.updateValueAndValidity();
    itemTypeCtl.markAsTouched();
    itemTypeCtl.updateValueAndValidity();

    // console.log('idx:' + idxCtl.value);

    if(this.secondFormGroup.status == 'VALID'){
      let name = scriptCtl.value.name;
      let id = scriptCtl.value.id;
      let result = [];
      switch(parseInt(itemTypeCtl.value)){
        case 1:
          if(resultCtl.value && resultCtl.value.length > 0){
            result = resultCtl.value;
          }else{
            T = false;
          }
          break;
        case 2:
          if(resultCtl.value && resultCtl.value.length > 0){
            result = resultCtl.value;
          }else{
            T = false;
          }
          break;
        case 3:
          result.push(resultCtl.value);
          break;
        default:
          result = [];
          break;
      }

      if(T){
        if(idxCtl.value == '*'){
          this.scriptList.push(new ScriptListModule(typeCtl.value, name, id, itemTypeCtl.value, result));
        }else{
          this.scriptList[idxCtl.value] = new ScriptListModule(typeCtl.value, name, id, itemTypeCtl.value, result)
        }

        event.next();
        this.secondFormGroup.reset();
        this.secondFormGroup.markAsTouched();
        this.secondFormGroup.updateValueAndValidity();
        this.secondFormGroup.get('idx').setValue('*');
      }else{
        this.snackBar.open('命令运行容器或实例不能为空！', '', {
          duration: 1000,
          panelClass: ['error-toaster']
        })
      }
      console.log(this.scriptList);
    }else{
      this.snackBar.open('选择命令和类型不能为空！', '', {
        duration: 1000,
        panelClass: ['error-toaster']
      })
    }

    return false;
  }

  toggleExpanded(idx){
    let status = this.scriptList[idx]['iconStatus'];
    this.scriptList.forEach(item => {
      item['iconStatus'] = false;
    });

    this.scriptList[idx]['iconStatus'] = !status;
    return false;
  }

  edit(item, event:MatStepper, idx){
    let typeCtl = this.secondFormGroup.get('type');
    typeCtl.setValue(item.typeCode);
    this.secondFormGroup.get('idx').setValue(idx);
    this.secondFormGroup.get('itemType').setValue(item.itemTypeCode);

    this.itemDefaultValue = item;
    this.setDefaultValue.setTaskList(this.itemDefaultValue);
    event.previous();
  }

  addItem(event){
    this.secondFormGroup.reset();
    this.secondFormGroup.markAsTouched();
    this.secondFormGroup.updateValueAndValidity();
    this.secondFormGroup.get('idx').setValue('*');
    event.previous();
  }

  delGroup(idx){
    this.scriptList.splice(idx, 1);
  }

  drop(event: CdkDragDrop<any[]>){
    let tempArr = this.scriptList;
    const prevIndex = tempArr.findIndex((d) => d === event.item.data);
    // console.log('prev idx: %d, current idx: %d', prevIndex, event.currentIndex);
    moveItemInArray(tempArr, prevIndex, event.currentIndex);
  }

  submit(event:MatStepper){
    if(this.scriptList.length == 0){
      this.snackBar.open('命令不能为空！', '', {
        duration: 1000,
        panelClass: ['error-toaster']
      });
      return false;
    }

    this.tools.checkForm(this.firstFormGroup);

    if(this.firstFormGroup.status == 'VALID'){
      let scriptList = [];

      this.scriptList.forEach(prop => {
        let item = this._getGroup(prop);
        item['type'] = (prop.typeCode == 200) ? 300 : 200;
        item['order_id'] = prop.id;
        item['scope'] = this._getScopeVal(prop);
        scriptList.push(item);
      });

      let formData = new HttpParams();
      formData = formData.set('pid', this.pid);
      formData = formData.set('name', this.firstFormGroup.get('name').value);
      formData = formData.set('description', this.firstFormGroup.get('description').value);
      formData = formData.set('script', JSON.stringify(scriptList));

      if(this.id){
        formData = formData.set('id', this.id);
        this.apiService.updateTask(formData, this.pid).subscribe((res) => {
          if(res['status'] == 200){
            this.tools.StatusSuccess(res, '操作成功');
            this.location.back();
          }else{
            this.tools.StatusError(res);
          }
        }, (error) => {
          this.tools.ServerError(error);
        })
      }else{
        this.apiService.createTask(formData, this.pid).subscribe((res) => {
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
    }else{
      event.selectedIndex = 0;
    }
  }

  private getDetail(){
    if(this.id){
      let formData = new HttpParams();
      formData = formData.set('id', this.id);
      this.apiService.getTask(formData, this.pid).subscribe((res) => {
        if(res['status'] == 200){
          let option = new TaskData<TaskModule>(res['data']);
          this.initForm(option.option[0]);
        }else{
          this.tools.StatusError(res);
        }
      }, (error) => {
        this.tools.ServerError(error);
      });
    }else{
      this.initForm();
    }
  }

  private initForm(data?){
    this.firstFormGroup = new FormGroup({
      name: new FormControl((data && data.name) ? data.name : '', Validators.required),
      description: new FormControl((data && data.description) ? data.description : '', Validators.required)
    });

    this.secondFormGroup = new FormGroup({
      idx: new FormControl('*'),
      type: new FormControl(''),
      script: new FormControl('', Validators.required),
      itemType: new FormControl(''),
      result: new FormControl('')
    });

    if(data){
      let formData = new HttpParams();
      formData = formData.set('id', this.id);
      this.apiService.getTaskDetail(formData, this.pid).subscribe((res) => {
        if(res['status'] == 200){
          let option = new TaskDetailData<ScriptListModule>(res['data']);
          this.scriptList = option.option;
          this.stepper.selectedIndex = 2;
        }else{
          this.tools.StatusError(res);
        }
      }, (error)=>{
        this.tools.ServerError(error);
      })
    }

    this.isShowForm = true;
  }

  private _getScopeVal(prop){
    let itemType = parseInt(prop.itemTypeCode);
    let type = parseInt(prop.typeCode);
    let result = '';

    switch(itemType){
      case 1:
        result = 'Group';
        break;
      case 2:
        result = (type == 200) ? 'dockList' : 'insList';
        break;
      case 3:
        result = 'customerGroup';
        break;
      default:
        result = 'all';
        break;
    }

    return result;
  }

  private _getGroup(prop){
    let itemType = parseInt(prop.itemTypeCode);
    let type = prop.typeCode;
    let result = {};

    switch(itemType){
      case 1:
        result['Group'] = prop.group.map(groupItem => groupItem.id);
        break;
      case 2:
        if(type == 100){
          result['insList'] = prop.group.map(groupItem => {
            return {
              type: 'gaea', //TODO
              id: groupItem.id
            }
          })
        }else{
          result['dockList'] = prop.group.map(groupItem => {
            return {
              name: groupItem.name,
              id: groupItem.id
            }
          })
        }
        break;
      case 3:
        result['customerGroup'] = prop.group.map(groupItem => groupItem.id);
        break;
      default:
        result = {};
        break;
    }

    return result;
  }
}

