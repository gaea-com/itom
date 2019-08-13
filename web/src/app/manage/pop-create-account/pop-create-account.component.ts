import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef, MatSnackBar} from "@angular/material";
import {ProjectListComponent} from "../../project/project-list/project-list.component";
import {FormControl, FormGroup, Validators} from "@angular/forms";
import {ToolsService} from "../../_Service/tools.service";
import {HttpParams} from "@angular/common/http";
import {ApiService} from "../../_Service/api.service";
import {statusModule} from "../../_Module/statusModule";

@Component({
  selector: 'app-pop-create-account',
  templateUrl: './pop-create-account.component.html',
  styleUrls: ['./pop-create-account.component.sass']
})
export class PopCreateAccountComponent implements OnInit {
  myForm:FormGroup;
  action:string;
  userId:number;
  roleList:string[] = ['root', 'admin'];
  isShowResult:boolean;
  password:string;
  statusList:statusModule[] = [
    {value: 200, name: "正常"},
    {value: 300, name: "异常或冻结"},
    {value: 400, name: "登录错误"}
  ];
  constructor(public dialogRef: MatDialogRef<ProjectListComponent>,
              public snackBar: MatSnackBar,
              @Inject(MAT_DIALOG_DATA) public data: any,
              public tools:ToolsService,
              public apiService:ApiService) { }

  ngOnInit() {
    if(this.data.popType == 'userForm'){
      this.isShowResult = false;
      this.userId = (this.data && this.data.id) ? this.data.id : '';
      this.action = (this.userId) ? '修改' : '创建';
      this.myForm = new FormGroup({
        name: new FormControl((this.data.name) ?  this.data.name : '', Validators.required),
        role: new FormControl((this.data.type) ? this.data.type : '', Validators.required),
        status: new FormControl((this.data.status) ? this.data.status : '')
      });
    }else{
      this.isShowResult = true;
      this.password = this.data.password;
    }
  }

  submitForm(){
    this.tools.checkForm(this.myForm);

    if(this.myForm.status == 'VALID'){
      let formData = new HttpParams();
      formData = formData.set('name', this.myForm.get('name').value);
      formData = formData.set('type', this.myForm.get('role').value);

      if(this.userId){
        formData = formData.set('status', this.myForm.get('status').value);
        this.apiService.updateUser(formData, this.userId).subscribe((res) => {
          if(res['status'] == 200){
            this.tools.StatusSuccess(res, '修改成功');
          }else{
            this.tools.StatusError(res);
          }
            this.dialogRef.close('done');
        }, (error) => {
          this.tools.ServerError(error);
        })
      }else{
        this.apiService.createUser(formData).subscribe((res) => {
          if(res['status'] == 200){
            this.isShowResult = true;
            this.password = res['code'];
          }else{
            this.tools.StatusError(res);
            this.dialogRef.close('done');
          }
        }, (error) => {
          this.tools.ServerError(error);
          this.dialogRef.close('done');
        })
      }
    }
  }

}
