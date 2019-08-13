import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialog, MatDialogRef} from "@angular/material";
import {FormBuilder, FormGroup, Validators} from "@angular/forms";
import {ProjectListComponent} from "../../project/project-list/project-list.component";
import {ApiService} from "../../_Service/api.service";
import {ToolsService} from "../../_Service/tools.service";
import {HttpParams} from "@angular/common/http";
import {TopologyComponent} from "../topology/topology.component";

@Component({
  selector: 'app-add-group',
  templateUrl: './add-group.component.html',
  styleUrls: ['./add-group.component.sass']
})
export class AddGroupComponent implements OnInit {
  myForm:FormGroup;
  constructor(public dialogRef: MatDialogRef<TopologyComponent>,
              @Inject(MAT_DIALOG_DATA) public data: any,
              public fb:FormBuilder,
              public tools:ToolsService,
              private apiService:ApiService) { }

  ngOnInit() {
    this.myForm = this.fb.group({
      type: [''],
      name: ['', Validators.required]
    });
  }

  confirm(){
    this.tools.checkForm(this.myForm);

    if(this.myForm.status == "VALID"){
      let formData = new HttpParams();
      formData = formData.set('name', this.myForm.get('name').value);
      formData = formData.set('type', this.myForm.get('type').value);
      formData = formData.set('pid', this.data.project_id);
      this.apiService.createGroup(formData, this.data.project_id).subscribe((res) => {
        if(res['status'] == 200){
          this.tools.StatusSuccess(res, '添加成功');
          this.dialogRef.close('done');
        }else{
          this.tools.StatusError(res);
        }
      }, (error) => {
        this.tools.ServerError(error);
      })
    }
  }
}
