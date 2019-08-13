import {Component, Input, OnInit} from '@angular/core';
import {ToolsService} from "../../_Service/tools.service";
import {ApiService} from "../../_Service/api.service";
import {MatSnackBar} from "@angular/material";
import {GroupData, GroupModule} from "../../_Module/GroupModule";
import {ContainerData, ContainerModule} from "../../_Module/ContainerModule";
import {HttpParams} from "@angular/common/http";
import {InstanceData, InstanceModule} from "../../_Module/InstanceModule";
import {CdkDragDrop, moveItemInArray, transferArrayItem} from "@angular/cdk/drag-drop";
import {FormControl, FormGroup} from "@angular/forms";

@Component({
  selector: 'app-select-group',
  templateUrl: './select-group.component.html',
  styleUrls: ['./select-group.component.sass']
})
export class SelectGroupComponent implements OnInit {
  @Input('fromOption') options:any[];
  @Input('toOption') selectedOptions:any[];
  @Input('option') groupOption:GroupModule[];
  @Input('myForm') myForm:FormGroup;
  @Input('pid') pid:string;

  todoSelections:any[] = [];
  doneSelections:any[] = [];
  hasDoneSelected:any = {};

  constructor(private tools:ToolsService,
              private apiService:ApiService,
              public snackBar: MatSnackBar,) { }

  ngOnInit() {
    this.setDefaultOption();
  }

  chooseGroup(event){
    let val = event.value;
    if(val){
      this.options = [];
      this.todoSelections = [];
      let formData = new HttpParams();
      formData = formData.set('gid', val);
      formData = formData.set('pid', this.pid);
      this.apiService.getTopologyList(formData, this.pid).subscribe((res) => {
        if(res['status'] == 200){
          let option = new InstanceData<InstanceModule>(res['data']);
          this.options = option.option;
          if(this.options.length > 0){
            this.options.forEach(prop => {
              let item = this._getInitOptionItem(prop.id);
              this.todoSelections.push(item);
            })
          }
        }else{
          this.tools.StatusError(res);
        }
      }, (error) => {
        this.tools.ServerError(error);
      })
    }
  }

  addItem(){
    this.switchArr('left');
    return false;
  }

  removeItem(){
    this.switchArr('right');
    return false;
  }

  deleteItem(element){
    let id = element.id;
    //添加
    this.options.push(element);
    let item = this._getInitOptionItem(id);
    this.todoSelections.push(item);

    //删除
    let selectedOptionIdx = this.tools.getIndex(this.selectedOptions, id, 'id');
    let doneOptionIdx = this._getIndex(this.doneSelections, id);
    // console.log('option idx: ' + selectedOptionIdx + 'todo idx: ' + doneOptionIdx);
    this.selectedOptions.splice(selectedOptionIdx, 1);
    this.doneSelections.splice(doneOptionIdx, 1);
    this.hasDoneSelected[id] = false;

    this.myForm.get('result').setValue(this.selectedOptions);
    return false;
  }

  drop(event: CdkDragDrop<any[]>) {
    if (event.previousContainer === event.container) {
      moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
    } else {
      let tempArr = JSON.parse(JSON.stringify(event.previousContainer.data));
      let currentItem = tempArr[event.previousIndex];

      if(!this.hasDoneSelected[currentItem.id]) {
        transferArrayItem(event.previousContainer.data,
          event.container.data,
          event.previousIndex,
          event.currentIndex);

        let item = this._getInitOptionItem(currentItem.id);
        this.doneSelections.push(item);

        //todoSelections
        this.todoSelections.splice(event.previousIndex, 1);
        this.todoSelections.forEach(prop => {
          Object.keys(prop).forEach(id => {
            prop[id] = false;
          })
        });
        this.selectedOptions.forEach(prop => {
          this.hasDoneSelected[prop.id] = true;
        });

        //update hasDoneSelected
        // console.log(this.todoSelections);
        // console.log(this.doneSelections);
        // console.log(this.options);
        // console.log(this.selectedOptions);

        this.myForm.get('result').setValue(this.selectedOptions);
        // console.log(this.hasDoneSelected);
      }else{
        let idx = this.tools.getIndex(this.options, currentItem.id, 'id');
        let selectedIdx = this._getIndex(tempArr, currentItem.id);
        this.options.splice(idx, 1);
        tempArr.splice(selectedIdx, 1);
      }
    }
  }

  //direction: left||right;
  private switchArr(direction){
    let selectedFromArr = (direction == 'left') ? this.todoSelections : this.doneSelections;
    let selectedToArr = (direction == 'left') ? this.doneSelections : this.todoSelections;
    let optionFromArr = (direction == 'left') ? this.options : this.selectedOptions;
    let optionToArr = (direction == 'left') ? this.selectedOptions : this.options;
    let tempArr = JSON.parse(JSON.stringify(selectedFromArr));

    selectedFromArr.forEach(prop => {
      Object.keys(prop).forEach(id => {
        if(prop[id]){
          let item = this.tools.getCurrentItem(optionFromArr, id, 'id');
          //添加
          if(direction == 'left'){
            if(this.hasDoneSelected[id]){
              //报错
              // console.log('已经添加过');
            }else{
              //添加
              let selectedItem = this._getInitOptionItem(id);
              optionToArr.push(item);
              selectedToArr.push(selectedItem);
              //增加标记
              this.hasDoneSelected[id] = true;
            }
          }else{
            if(this.hasDoneSelected[id]){
              //添加 去除标记
              this.hasDoneSelected[id] = false;

              let selectedItem = this._getInitOptionItem(id);
              optionToArr.push(item);
              selectedToArr.push(selectedItem);
            }else{
              //异常报错
              // console.log('异常报错')
            }
          }

          //删除
          let idx = this.tools.getIndex(optionFromArr, id, 'id');
          let selectedIdx = this._getIndex(tempArr, id);
          // console.log('option idx: ' + idx + 'todo idx: ' + selectedIdx);
          optionFromArr.splice(idx, 1);
          tempArr.splice(selectedIdx, 1);
        }
      })
    });

    if(direction == 'left'){
      this.todoSelections = tempArr;
    }else{
      this.doneSelections = tempArr;
    }

    this.myForm.get('result').setValue(this.selectedOptions);
  }

  private setDefaultOption(){
    if(this.options && this.options.length > 0){
        this.options.forEach(prop => {
          let item = this._getInitOptionItem(prop.id);
          this.todoSelections.push(item);
        })
    }

    if(this.selectedOptions && this.selectedOptions.length > 0){
      this.selectedOptions.forEach(prop => {
        let item = this._getInitOptionItem(prop.id);
        this.doneSelections.push(item);
        this.hasDoneSelected[prop.id] = true;
      });
    }
  }

  private _getInitOptionItem(prop){
    let item = {}
    item[prop] = false;
    return item
  }

  public _getIndex(arr, id){
    let idx:number = 0;
    if(arr.length > 0){
      for(let i = 0; i < arr.length; i++){
        Object.keys(arr[i]).forEach(key => {
          if(key == id){
            idx = i;
            return idx;
          }
        });
      }
    }

    return idx;
  }
}
