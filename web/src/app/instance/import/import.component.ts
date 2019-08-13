import {Component, Inject, OnInit, ViewChild} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef} from "@angular/material";
import {ProjectListComponent} from "../../project/project-list/project-list.component";
import {TopologyComponent} from "../topology/topology.component";
import {FormBuilder, FormGroup, Validators} from "@angular/forms";
import {Subscription} from "rxjs/Subscription";
import {OvserveFileService} from "../../_Service/ovserve-file.service";
import {ApiService} from "../../_Service/api.service";
import {HttpEventType} from "@angular/common/http";
import {ToolsService} from "../../_Service/tools.service";

@Component({
  selector: 'app-import',
  templateUrl: './import.component.html',
  styleUrls: ['./import.component.sass']
})
export class ImportComponent implements OnInit {
  @ViewChild('File', {static: true}) FileInput: any;
  myForm:FormGroup;
  private fileService : OvserveFileService;
  private subscription: Subscription;
  uploadingProgressing: boolean = false;
  uploadProgress: number = 0;
  uploadComplete: boolean = false;
  constructor(public dialogRef: MatDialogRef<TopologyComponent>,
              @Inject(MAT_DIALOG_DATA) public data: any,
              private fb:FormBuilder,
              private _fileService: OvserveFileService,
              private apiService: ApiService,
              private tools:ToolsService) {
    this.fileService = _fileService;

    this.subscription = _fileService.fileChange$.subscribe( file => {
      if(this.FileInput.nativeElement.files[0]){
        let currentFile = this.FileInput.nativeElement.files[0];
        this.myForm.patchValue({
          choose_file: currentFile.name
        })
      }
    });
  }

  ngOnInit() {
    this.myForm = this.fb.group({
      choose_file: ['', Validators.required]
    });
  }

  chooseFile(){
    this.fileService.setFile();
  }

  confirm(){
    let formData: FormData = new FormData();
    formData.append('project_id', this.data.project_id);
    formData.append('file', this.FileInput.nativeElement.files[0]);
    this.apiService.importInstance(formData, this.data.project_id,(data) =>{
      this.handleProgress(data);
    });

  }

  handleProgress(event){
    if (event.type === HttpEventType.UploadProgress) {
      this.uploadingProgressing = true;
      this.uploadProgress = Math.round(100 * event.loaded / event.total);
      //console.log(this.uploadProgress);
    } else if (event.type === HttpEventType.Response) {
      this.uploadComplete = true;
      if(event.body.status == 200){
        this.tools.StatusSuccess({},'导入成功');
        this.dialogRef.close('done');
      }else{
        // let msg = event.body.errorMsg;
        this.tools.StatusError(event.body);
      }
    }
  }
}
