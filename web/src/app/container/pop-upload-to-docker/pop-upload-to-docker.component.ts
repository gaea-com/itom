import {Component, Inject, OnInit, ViewChild} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef, MatSnackBar} from "@angular/material";
import {ApiService} from "../../_Service/api.service";
import {ProjectListComponent} from "../../project/project-list/project-list.component";
import {ToolsService} from "../../_Service/tools.service";
import {ContainerListComponent} from "../container-list/container-list.component";
import {FormBuilder, FormControl, FormGroup, Validators} from "@angular/forms";
import {OvserveFileService} from "../../_Service/ovserve-file.service";
import {Subscription} from "rxjs/Subscription";
import {HttpEventType, HttpParams} from "@angular/common/http";

@Component({
  selector: 'app-pop-upload-to-docker',
  templateUrl: './pop-upload-to-docker.component.html',
  styleUrls: ['./pop-upload-to-docker.component.sass']
})
export class PopUploadToDockerComponent implements OnInit {

  fileForm:FormGroup;
  @ViewChild('File', {static: true}) FileInput: any;
  uploadingProgressing: boolean = false;
  uploadProgress: number = 0;
  uploadComplete: boolean = false;
  private fileService:OvserveFileService;
  fileSubscription:Subscription;
  title:string;
  type:string;
  action:string;
  constructor(    private apiServer: ApiService,
                  public tools:ToolsService,
                  public dialogRef: MatDialogRef<ContainerListComponent>,
                  @Inject(MAT_DIALOG_DATA) public data: any,
                  public snackBar: MatSnackBar,
                  public _fileService:OvserveFileService,
                  public fb:FormBuilder) {
    this.fileService = _fileService;
    this.fileSubscription = this.fileService.fileChange$.subscribe((res) =>{
      if(this.FileInput.nativeElement.files[0]){
        let currentFile = this.FileInput.nativeElement.files[0];
        this.fileForm.patchValue({
          file: currentFile.name
        })
      }
    })
  }

  ngOnInit() {
    this.title = this.data.title;
    this.type = this.data.type;
    this.action = (this.data.type == 'upload') ? '上传' : '下载';

    let item = {
      'path': ['', Validators.required]
    }
    if(this.data.type == 'upload'){
      item['file'] = ['', Validators.required]
    }
    this.fileForm = this.fb.group(item);
    console.log(this.fileForm);
  }

  submitForm(){
    let pathCtl= this.fileForm.get('path');
    pathCtl.markAsTouched();
    pathCtl.updateValueAndValidity();
    if(this.type == 'upload'){
      let fileCtl = this.fileForm.get('file');
      fileCtl.markAsTouched();
      fileCtl.updateValueAndValidity();
    }

    if(this.fileForm.status == 'VALID'){
      let formData = new FormData();
      if(this.type == 'upload'){
        formData.append('target_ip', this.data.ip);
        formData.append('container_id', this.data.code);
        formData.append('path', pathCtl.value);
        formData.append('file', this.FileInput.nativeElement.files[0]);
        this.apiServer.uploadToDocker(formData, (data) =>{
          this.handleProgress(data);
        })
      }else{
        let baseUrl = this.apiServer.getBaseUrl();
        var url = baseUrl + 'docker_download?target_ip='+ this.data.ip +
                  '&container_id='+ this.data.code + '&path=' + pathCtl.value;
        window.open(url);
        this.dialogRef.close();
      }

    }else{
      this.snackBar.open('表单验证未通过', '',{
        duration: 1000,
        panelClass: ['error-toaster']
      })
    }
  }

  handleProgress(event){
    if (event.type === HttpEventType.UploadProgress) {
      this.uploadingProgressing = true;
      this.uploadProgress = Math.round(100 * event.loaded / event.total);
    } else if (event.type === HttpEventType.Response) {
      this.uploadComplete = true;
      if(event.body.message == "success"){
        this.tools.StatusSuccess({},'上传成功');
        this.dialogRef.close();
      }
    }else{
      if(event.error){
        let msg =  event.error.message;
          this.snackBar.open(msg, '',{
            duration: 3000,
            panelClass: ['error-toaster']
          })
        this.dialogRef.close();
      }
    }
  }

  chooseFile(){
    this.fileService.setFile();
  }
}
