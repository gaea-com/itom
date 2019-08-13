import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef, MatSnackBar} from "@angular/material";
import {ApiService} from "../../_Service/api.service";
import {ProjectListComponent} from "../../project/project-list/project-list.component";
import {ToolsService} from "../../_Service/tools.service";
import {FormControl, FormGroup, Validators} from "@angular/forms";
import {UserData, UserModule} from "../../_Module/UserModule";
import {HttpParams} from "@angular/common/http";


@Component({
  selector: 'app-pop-perm',
  templateUrl: './pop-perm.component.html',
  styleUrls: ['./pop-perm.component.sass']
})
export class PopPermComponent implements OnInit {
  myForm:FormGroup;
  userList:UserModule[];
  constructor(public dialogRef: MatDialogRef<ProjectListComponent>,
              public snackBar: MatSnackBar,
              @Inject(MAT_DIALOG_DATA) public data: any,
              public tools:ToolsService,
              public apiService:ApiService) { }

  ngOnInit() {
    this.myForm = new FormGroup({
      user: new FormControl('', Validators.required)
    });
    this.getUserList();
  }

  confirm(){
    this.tools.checkForm(this.myForm);

    if(this.myForm.status == 'VALID'){
      let formData = new HttpParams();
      let userResult = [];
      let userCtl = this.myForm.get('user').value;
      userCtl.forEach(prop => {
        let item = {}
        item[prop.id] = prop.name;
        userResult.push(item);
      });
      formData = formData.set('pid', this.data.id);
      formData = formData.set('uid', JSON.stringify(userResult));
      this.apiService.perm(formData).subscribe((res) => {
        if(res['status'] == 200){
          this.tools.StatusSuccess(res, '授权成功');
          this.dialogRef.close('done');
        }else{
          this.tools.StatusError(res);
          this.dialogRef.close();
        }
      }, (error) => {
        this.tools.ServerError(error);
      })
    }else{
      this.snackBar.open('请选择用户', '', {
        duration: 5000,
        panelClass: ['success-toaster']
      })
    }
  }

  private getUserList(){
    this.apiService.getUser().subscribe((res) => {
      if(res['status'] == 200){
        let option = new UserData<UserModule>(res['data']);
        this.userList = option.option;
      }else{
        this.tools.StatusError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }
}
