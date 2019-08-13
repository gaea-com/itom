import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef, MatSnackBar} from "@angular/material";
import {ComposeListComponent} from "../compose-list/compose-list.component";
import {FormArray, FormBuilder, FormGroup, Validators} from "@angular/forms";
import {ToolsService} from "../../_Service/tools.service";
import {HttpParams} from "@angular/common/http";
import {ApiService} from "../../_Service/api.service";
import {CdkDragDrop, moveItemInArray} from "@angular/cdk/drag-drop";

@Component({
  selector: 'app-add-compose',
  templateUrl: './add-compose.component.html',
  styleUrls: ['./add-compose.component.sass']
})
export class AddComposeComponent implements OnInit {
  myForm:FormGroup;
  isShowForm:boolean = false;
  action:string;
  imageList:number[];
  constructor(public dialogRef: MatDialogRef<ComposeListComponent>,
              @Inject(MAT_DIALOG_DATA) public data: any,
              public fb:FormBuilder,
              public snackBar: MatSnackBar,
              private apiService:ApiService,
              private tools:ToolsService) { }

  get imageListControl(){
    return this.myForm.get('imageList') as FormArray;
  }

  ngOnInit() {
    this.action = (this.data.id) ? '创建' : '修改';
    this.initForm();
  }

  addImageItem(defaultVal?){
    if(!defaultVal){
      this.imageListControl.controls.forEach((item, i) => {
        let imageCtl = item.get('image_name');
        imageCtl.markAsTouched();
        imageCtl.updateValueAndValidity();
      });
    }

    if(this.imageListControl.status == 'VALID'){

      this.imageList = [];
      this.imageListControl.controls.forEach((item, i) => {
        this.imageList.push(i);
      });

      let item = this.fb.group({
        image_name: ['', Validators.required],
        sleep_time: [0]
      });


      this.imageListControl.push(item);

      if(defaultVal){
        let endIndex = this.imageListControl.length - 1;
        let itemCtl = this.imageListControl.get(endIndex.toString()) as FormGroup;
        itemCtl.get('image_name').setValue(defaultVal['image']);
        itemCtl.get('sleep_time').setValue(defaultVal['sleep_time']);
      }
    }

    return false;
  }

  drop(event: CdkDragDrop<any[]>) {
    let tempArr = this.imageListControl.value;
    let prevIndex = tempArr.findIndex((d) => d === event.item.data);
    // console.log('prev index: ' + prevIndex);
    // console.log('current index' + event.currentIndex);
    moveItemInArray(tempArr, prevIndex, event.currentIndex);
    // console.log(this.imageListControl);

    tempArr.forEach((item, i) => {
      let ctl = this.imageListControl.get(i.toString()) as FormGroup;
      ctl.get('image_name').setValue(item['image_name']);
      ctl.get('sleep_time').setValue(item['sleep_time']);
    })
  }

  removeImageItem(i){
    if(i || this.imageListControl.length > 1){
      this.imageListControl.removeAt(i);
    }else{
      this.snackBar.open('最后一项无法删除', '',{
        duration: 1000,
        panelClass: ['error-toaster']
      });
    }
    return false;
  }

  confirm(){

    let nameCtl = this.myForm.get('name');
    let descriptionCtl = this.myForm.get('description');
    nameCtl.markAsTouched();
    nameCtl.updateValueAndValidity();

    this.imageListControl.controls.forEach((item, i) => {
      let imageCtl = item.get('image_name');
      imageCtl.markAsTouched();
      imageCtl.updateValueAndValidity();
    });

    if(this.myForm.status == "VALID"){
      let formData = new HttpParams();
      formData = formData.set('project_id', this.data.project_id);
      formData = formData.set('name', nameCtl.value);
      formData = formData.set('description', descriptionCtl.value);
      formData = formData.set('image_name', JSON.stringify(this.imageListControl.value));

      if(this.data.id){
        this.apiService.putComponse(formData, this.data.id, this.data.project_id).subscribe((res) => {
          if(res['status'] == 200){
            this.tools.StatusSuccess(res, '修改成功');
            this.dialogRef.close('done');
          }else{
            this.tools.StatusError(res);
          }
        }, (error) => {
          this.tools.StatusError(error);
        })
      }else{
        this.apiService.createComponse(formData, this.data.project_id).subscribe((res) => {
          if(res['status'] == 200){
            this.tools.StatusSuccess(res, '添加成功');
            this.dialogRef.close('done');
          }else{
            this.tools.StatusError(res);
          }
        }, (error) => {
          this.tools.StatusError(error);
        })
      }
    }
  }

  private initForm(){
    this.myForm = this.fb.group({
      name: ['', Validators.required],
      description: [''],
      imageList: this.fb.array([])
    });

    if(this.data.id){
      this.myForm.get('name').setValue(this.data.name);
      this.myForm.get('description').setValue(this.data.description);

      this.data.image_list.forEach(item => {
        console.log(item);
        this.addImageItem(item);
      });
    }else{
      this.addImageItem();
    }

    this.isShowForm = true;
  }
}

