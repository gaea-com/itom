import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef, MatSnackBar} from "@angular/material";
import {TopologyComponent} from "../topology/topology.component";
import {FormBuilder, FormGroup, Validators} from "@angular/forms";
import {ApiService} from "../../_Service/api.service";
import {ToolsService} from "../../_Service/tools.service";
import {ComposeData, ComposeModule, ComposeOptionMoudle} from "../../_Module/ComposeModule";
import {InstanceModule} from "../../_Module/InstanceModule";
import {HttpParams} from "@angular/common/http";

@Component({
  selector: 'app-link-instance',
  templateUrl: './link-instance.component.html',
  styleUrls: ['./link-instance.component.sass']
})
export class LinkInstanceComponent implements OnInit {
  myForm:FormGroup;
  instanceOption:InstanceModule[];
  composeOption:ComposeOptionMoudle[];
  constructor(public dialogRef: MatDialogRef<TopologyComponent>,
              @Inject(MAT_DIALOG_DATA) public data: any,
              private fb:FormBuilder,
              private apiService:ApiService,
              public snackBar: MatSnackBar,
              private tools: ToolsService) { }

  ngOnInit() {

    this.instanceOption = this.data.unbindInstanceList;
    this.myForm = this.fb.group({
      compose: ['', Validators.required],
      instance: ['', Validators.required],
      description: ['',Validators.required]
    });
    this.getComposeList();
  }

  confirm(){
    this.tools.checkForm(this.myForm);

    if(this.myForm.status == 'VALID'){
      let formData = new HttpParams();
      formData = formData.set('description', this.myForm.value.description);
      formData = formData.set('group_id', this.data.groupId);
      formData = formData.set('project_id', this.data.project_id);
      formData = formData.set('instance_id', this.myForm.value.instance);
      formData = formData.set('compose_id', this.myForm.value.compose);
      formData = formData.set('cloud_type', 'gaea');
      this.apiService.linkGroup(formData, this.data.project_id).subscribe((res) => {
        if(res['status'] == 200){
          this.tools.StatusSuccess(res, '关联成功');
          this.dialogRef.close({
            status: 'done',
            instance: this.myForm.value.instance
          })
        }else{
          this.tools.StatusError(res);
        }
      }, (error) => {
        this.tools.ServerError(error);
      })
    }
  }

  private getComposeList(){
    let formData = new HttpParams();
    formData = formData.append('pid', this.data.project_id);
    this.apiService.getCompose(formData, this.data.project_id).subscribe((res) => {
      if(res['status'] == 200){
        let option = new ComposeData<ComposeOptionMoudle>(res['data']);
        this.composeOption = option.option;

        if(this.composeOption.length == 0){
          this.snackBar.open('暂无编排模版，请先创建！', '',{
            duration: 1000,
            panelClass: ['error-toaster']
          });
          this.dialogRef.close();
        }
      }else{
        this.tools.StatusError(res);
      }
    }, (error) => {
      this.tools.StatusError(error);
    })
  }

}
