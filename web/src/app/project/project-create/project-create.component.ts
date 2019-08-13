import { Component, OnInit } from '@angular/core';
import {FormBuilder, FormGroup, Validators} from "@angular/forms";
import {ActivatedRoute} from "@angular/router";
import {ProjectStatus, statusModule} from "../../_Module/statusModule";
import {ToolsService} from "../../_Service/tools.service";
import {Location} from "@angular/common";
import {HttpParams} from "@angular/common/http";
import {ApiService} from "../../_Service/api.service";
import {ProjectData, ProjectModule} from "../../_Module/ProjectModule";

@Component({
  selector: 'app-project-create',
  templateUrl: './project-create.component.html',
  styleUrls: ['./project-create.component.sass']
})
export class ProjectCreateComponent implements OnInit {
  myForm:FormGroup;
  pid:number|any = null;
  statusOption: statusModule[];
  isShowForm:boolean = false;
  constructor( private route: ActivatedRoute,
               private fb: FormBuilder,
               public tools:ToolsService,
               private apiService:ApiService,
               private location: Location) { }

  ngOnInit() {
    this.route.params.subscribe(params => {

      if(params.params){
        this.pid = parseInt(params.params);
        this.getProject();
      }else{
        this.initForm();
      }
    });
    this.statusOption = ProjectStatus;
  }

  submitForm(){

    this.tools.checkForm(this.myForm);

    if(this.myForm.status == 'VALID'){
      let formData = new HttpParams();
      formData = formData.set('name', this.myForm.get('name').value);
      formData = formData.set('description', this.myForm.get('description').value);

      if(this.pid){
        this.apiService.putProject(formData, this.pid).subscribe((res) => {
          if(res['status'] == 200){
            this.tools.StatusSuccess(res, '修改成功');
            this.location.back();
          }else{
            this.tools.StatusError(res);
          }
        }, (error) => {
          this.tools.ServerError(error);
        })
      }else{
        this.apiService.postProject(formData).subscribe((res) => {
          if(res['status'] == 200){
            this.tools.StatusSuccess(res, '创建成功');
            this.location.back();
          }else{
            this.tools.StatusError(res);
          }
        }, (error) => {
          this.tools.ServerError(error);
        })
      }
    }
  }

  private initForm(data?){
    this.myForm = this.fb.group({
      'name': ['', [Validators.required, Validators.pattern('^[A-Z][a-zA-Z0-9_]*$')]],
      'description': [''],
    });

    if(data){
      this.myForm.get('name').setValue(data.name);
      this.myForm.get('name').disable();
      this.myForm.get('description').setValue(data.description);
    }
    this.isShowForm = true;
  }

  private getProject(){
    let formData = new HttpParams();
    formData = formData.set('id', this.pid);

    this.apiService.getProject(formData).subscribe((res) => {
      if(res['status'] == 200){
        let option = new ProjectData<ProjectModule>(res['data']);
        this.initForm(option['option'][0]);
      }else{
        this.tools.StatusError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }
}
