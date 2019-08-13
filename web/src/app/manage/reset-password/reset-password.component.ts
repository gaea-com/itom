import { Component, OnInit } from '@angular/core';
import {ProjectListComponent} from "../../project/project-list/project-list.component";
import {MatDialogRef, MatSnackBar} from "@angular/material";
import {HomeComponent} from "../../home/home.component";
import {FormControl, FormGroup, Validators} from "@angular/forms";
import {ToolsService} from "../../_Service/tools.service";
import {ApiService} from "../../_Service/api.service";
import {HttpParams} from "@angular/common/http";

@Component({
  selector: 'app-reset-password',
  templateUrl: './reset-password.component.html',
  styleUrls: ['./reset-password.component.sass']
})
export class ResetPasswordComponent implements OnInit {
  myForm:FormGroup;
  constructor(public dialogRef: MatDialogRef<HomeComponent>,
              public tools:ToolsService,
              public snackBar: MatSnackBar,
              public apiService:ApiService) { }

  ngOnInit() {
    this.myForm = new FormGroup({
      oldPassword: new FormControl('', Validators.required),
      password1: new FormControl('', Validators.required),
      password2: new FormControl('', Validators.required)
    })
  }

  confirm(){
    this.tools.checkForm(this.myForm);

    if(this.myForm.status == 'VALID'){
      let pws1 = this.myForm.get('password1').value;
      let pws2 = this.myForm.get('password2').value;
      if(pws1 != pws2){
        this.snackBar.open('两次密码输入不正确，请重新输入', '', {
          duration: 1000,
          panelClass: ['error-toaster']
        });

        return false
      }

      let formData = new HttpParams();
      formData = formData.set('old_passwd', this.myForm.get('oldPassword').value);
      formData = formData.set('new_passwd', this.myForm.get('password1').value);
      this.apiService.userResetPassword(formData).subscribe((res) => {
        if(res['status'] == 200){
          this.tools.StatusSuccess(res, '修改成功');
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
