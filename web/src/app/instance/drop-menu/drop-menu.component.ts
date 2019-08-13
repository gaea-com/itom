import {Component, EventEmitter, Input, OnInit, Output, ViewChild} from '@angular/core';
import {FormArray, FormBuilder, FormGroup, Validators} from "@angular/forms";
import {HttpEventType, HttpParams} from "@angular/common/http";
import {ApiService} from "../../_Service/api.service";
import {ToolsService} from "../../_Service/tools.service";
import {AddComposeComponent} from "../../compose/add-compose/add-compose.component";
import {MatDialog} from "@angular/material";
import {OvserveFileService} from "../../_Service/ovserve-file.service";
import {Subscription} from "rxjs/Subscription";

@Component({
  selector: 'app-drop-menu',
  templateUrl: './drop-menu.component.html',
  styleUrls: ['./drop-menu.component.sass']
})
export class DropMenuComponent implements OnInit {
  addForm:FormGroup;
  @Input('pid') pid:number;
  @Output('getValue') E = new EventEmitter<any>();

  @ViewChild('File', {static: true}) FileInput: any;
  importForm:FormGroup;
  private fileService : OvserveFileService;
  private subscription: Subscription;
  uploadingProgressing: boolean = false;
  uploadProgress: number = 0;
  uploadComplete: boolean = false;
  constructor(private fb:FormBuilder,
              private apiService:ApiService,
              private tools:ToolsService,
              private _fileService: OvserveFileService,
              public dialog: MatDialog) {
    this.fileService = _fileService;

    this.subscription = _fileService.fileChange$.subscribe( file => {
      if(this.FileInput.nativeElement.files[0]){
        let currentFile = this.FileInput.nativeElement.files[0];
        this.importForm.patchValue({
          choose_file: currentFile.name
        })
      }
    });
  }

  get groupNameControl(){
    return this.addForm.get('groupName') as FormArray;
  }

  ngOnInit() {
    this.addForm = this.fb.group({
      groupName: ['', Validators.required]
    });

    this.importForm = this.fb.group({
      choose_file: ['', Validators.required]
    });
  }

  addGroup(){
    if(this.addForm.status == 'VALID'){
      let formData = new HttpParams();
      formData = formData.set('name', this.groupNameControl.value);
      formData = formData.set('type', '200');
      formData = formData.set('pid', this.pid.toString());
      this.apiService.createGroup(formData, this.pid).subscribe((res) => {
        if(res['status'] == 200){
          this.tools.StatusSuccess(res, '添加成功');
          this.addForm.reset();
          this.E.emit({
            "event": 'addGroup',
            "status": 'done'
          });
        }else{
          this.tools.StatusError(res);
        }
      }, (error) => {
        this.tools.ServerError(error);
      })
    }
  }

  submitUpload(){
    let formData: FormData = new FormData();
    formData.append('project_id', this.pid.toString());
    formData.append('file', this.FileInput.nativeElement.files[0]);
    this.apiService.importInstance(formData, this.pid, (data) =>{
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
        this.importForm.reset();
        this.E.emit({
          "event": 'importInstance',
          "status": 'done'
        });
      }else{
        this.uploadingProgressing = false;
        this.tools.StatusError(event.body);
      }
    }
  }

  createCompose(){
    let dialogRef = this.dialog.open(AddComposeComponent, {
      height: '60%',
      width: '50%',
      disableClose: true,
      autoFocus: false,
      data: {
        project_id: this.pid
      }
    });

    this.E.emit({
      "event": 'addCompose',
      "status": 'done'
    });
  }

  chooseFile(){
    this.fileService.setFile();
  }
}
