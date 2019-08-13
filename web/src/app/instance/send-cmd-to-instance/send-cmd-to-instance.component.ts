import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef} from "@angular/material";
import {InstanceComponent} from "../instance/instance.component";
import {FormControl, FormGroup, Validators} from "@angular/forms";
import {ToolsService} from "../../_Service/tools.service";
import {ApiService} from "../../_Service/api.service";
import {HttpParams} from "@angular/common/http";

@Component({
  selector: 'app-send-cmd-to-instance',
  templateUrl: './send-cmd-to-instance.component.html',
  styleUrls: ['./send-cmd-to-instance.component.sass']
})
export class SendCmdToInstanceComponent implements OnInit {

constructor(public dialogRef: MatDialogRef<InstanceComponent>,
            @Inject(MAT_DIALOG_DATA) public data: any,
            private tools: ToolsService,
            private apiService:ApiService,) { }
  myForm:FormGroup;
  ngOnInit() {
    this.myForm = new FormGroup({
      cmd: new FormControl('', Validators.required)
    });
  }

  submit(){
    this.tools.checkForm(this.myForm);

    if(this.myForm.status == 'VALID'){
      let formData = new HttpParams();
      if(this.data.type == 100){
        formData = formData.set('pid', this.data.pid);
        formData = formData.set('cmd', this.myForm.get('cmd').value);
        formData = formData.set('request', JSON.stringify(this.data.request));
        formData = formData.set('type', 'command');
        this.apiService.sendCmdToInstance(formData, this.data.pid).subscribe((res) => {
          if(res['status'] == 200){
            this.tools.StatusSuccess(res, '发送成功，请耐心等待');
            this.dialogRef.close();
          }else{
            this.tools.StatusError(res);
          }
        }, (error) => {
          this.tools.ServerError(error);
        })
      }else{
        formData = formData.set('id', JSON.stringify(this.data.request));
        formData = formData.set('cmd', this.myForm.get('cmd').value);
        this.apiService.sendCmd(formData, this.data.pid).subscribe((res) => {
          if(res['status'] == 200){
            this.tools.StatusSuccess(res, '命令发送成功，请耐心等待结果～');
            this.dialogRef.close();
          }else{
            this.tools.StatusError(res)
          }
        }, (error) => {
          this.tools.ServerError(error);
        })
      }
    }
  }
}
