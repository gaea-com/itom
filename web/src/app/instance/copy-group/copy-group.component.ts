import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef, MatSnackBar} from "@angular/material";
import {ApiService} from "../../_Service/api.service";
import {ToolsService} from "../../_Service/tools.service";
import {TopologyComponent} from "../topology/topology.component";
import {FormArray, FormBuilder, FormGroup, Validators} from "@angular/forms";
import {HttpParams} from "@angular/common/http";

@Component({
  selector: 'app-copy-group',
  templateUrl: './copy-group.component.html',
  styleUrls: ['./copy-group.component.sass']
})
export class CopyGroupComponent implements OnInit {
  myForm:FormGroup;
  constructor(public dialogRef: MatDialogRef<TopologyComponent>,
              @Inject(MAT_DIALOG_DATA) public data: any,
              private fb:FormBuilder,
              private apiService:ApiService,
              public snackBar: MatSnackBar,
              private tools: ToolsService) { }

  get instanceListControl(){
    return this.myForm.get('instanceList') as FormArray;
  }

  ngOnInit() {

    this.myForm = this.fb.group({
      name: ['', Validators.required],
      type: ['100'],
      instanceList: this.fb.array([])
    });

    if(this.data.group && this.data.group.length > 0){
      this.data.group.forEach(prop => {
        let item = this.fb.group({
          id: [prop.id],
          instance_name: [{value: prop.name, disabled:true}],
          new_instance_name: [prop.name+'-copy', Validators.required],
          new_instance_description: [prop.description + '-copy', Validators.required]
        });

        this.instanceListControl.push(item);
      });
    }

    console.log(this.myForm);
  }

  confirm(){
    let nameCtrl = this.myForm.get('name');
    nameCtrl.markAsTouched();
    nameCtrl.updateValueAndValidity();

    this.instanceListControl.controls.forEach((item:FormGroup) => {
      this.tools.checkForm(item);
    })

    if(this.myForm.status == 'VALID'){
      let group = [];
      this.instanceListControl.value.forEach(prop => {
        let item = {};
        item['id'] = prop.id;
        item['name'] = prop.new_instance_name;
        item['description'] = prop.new_instance_description;
        item['cloudtype'] = 'gaea';
        group.push(item);
      });
      let formData = new HttpParams();
      formData = formData.set('name', nameCtrl.value);
      formData = formData.set('gid', this.data.groupId);
      formData = formData.set('group', JSON.stringify(group));

      this.apiService.copyGroup(formData, this.data.project_id).subscribe((res) => {
        if(res['status'] == 200){
          this.tools.StatusSuccess(res, '任务请提交，请稍候片刻~');
          this.dialogRef.close();
        }else{
          this.tools.StatusError(res);
        }
      },(error) => {
        this.tools.ServerError(error);
      })
    }
  }
}
