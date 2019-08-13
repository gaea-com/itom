import { Component, OnInit } from '@angular/core';
import {FormControl, FormGroup, Validators} from "@angular/forms";
import {ToolsService} from "../../_Service/tools.service";
import {ApiService} from "../../_Service/api.service";
import {HttpParams} from "@angular/common/http";
import {ActivatedRoute} from "@angular/router";
import {MatDialog, MatSnackBar} from "@angular/material";
import {Location} from "@angular/common";
import {ScriptData, ScriptModule} from "../../_Module/ScriptModule";

@Component({
  selector: 'app-script-create',
  templateUrl: './script-create.component.html',
  styleUrls: ['./script-create.component.sass']
})
export class ScriptCreateComponent implements OnInit {
  pid:string;
  myForm:FormGroup;
  id:string;
  isShowForm:boolean = false;
  constructor(private tools:ToolsService,
              private apiService:ApiService,
              public snackBar: MatSnackBar,
              private location: Location,
              private route: ActivatedRoute) { }

  ngOnInit() {
    this.route.parent.params.subscribe(params => {
      if(params.params){
        this.tools.parseParams(params.params, (obj) => {
          this.pid = obj['pid'];
        })
      }
    });

    this.route.params.subscribe(params => {
      if(params.params){
        this.tools.parseParams(params.params, (obj) => {
          this.id = obj['id'];
          this.getDetail();
        })
      }else{
        this.initForm();
      }
    });
  }

  selectType(event){
    let val = event.value;

    if(val == '200'){
      this.myForm.get('script').setValue('#!/bin/bash');
    }else{
      this.myForm.get('script').setValue('');
    }
  }

  submitForm(){
    this.tools.checkForm(this.myForm);

    if(this.myForm.status == 'VALID'){
      let formData = new HttpParams();
      formData = formData.set('pid', this.pid);
      formData = formData.set('name', this.myForm.value.name);
      formData = formData.set('description', this.myForm.value.description);
      formData = formData.set('type', this.myForm.value.type);
      formData = formData.set('share', (this.myForm.value.share) ? '1' : '0');
      formData = formData.set('order', this.myForm.value.script);
      if(this.id){
        formData = formData.set('id', this.id);
        this.apiService.editScript(formData, this.pid).subscribe((res) =>{
          if(res['status'] == 200){
            this.tools.StatusSuccess(res, '创建成功');
            this.location.back();
          }else{
            this.tools.StatusError(res);
          }
        }, (error) => {
          this.tools.ServerError(error);
        });
      }else{
        this.apiService.createScript(formData, this.pid).subscribe((res) =>{
          if(res['status'] == 200){
            this.tools.StatusSuccess(res, '创建成功');
            this.location.back();
          }else{
            this.tools.StatusError(res);
          }
        }, (error) => {
          this.tools.ServerError(error);
        });
      }

    }else{
      this.snackBar.open('类型不能为空', '',{
        duration: 1000,
        panelClass: ['error-toaster']
      });
    }
  }

  private getDetail(){
    let formData = new HttpParams();
    formData = formData.set('id', this.id);
    this.apiService.getScript(formData, this.pid).subscribe((res) => {
      let option = new ScriptData<ScriptModule>(res['data']);
      this.initForm(option.option[0])
    }, (error) => {
      this.tools.ServerError(error);
    })
  }

  private initForm(data?:ScriptModule){
    this.myForm = new FormGroup({
      name: new FormControl((data) ? data.name : '', Validators.required),
      description: new FormControl((data) ? data.description : '', Validators.required),
      type: new FormControl((data) ? data.typeCode : '', Validators.required),
      share: new FormControl((data) ? data.canShare : ''),
      script: new FormControl((data) ? data.script : '')
    });

    this.isShowForm = true;
  }

}
