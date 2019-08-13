import { Component, OnInit } from '@angular/core';
import {Location} from "@angular/common";
import {ActivatedRoute} from "@angular/router";
import {ToolsService} from "../../_Service/tools.service";
import {ApiService} from "../../_Service/api.service";
import {MatSnackBar} from "@angular/material";
import {FormControl, FormGroup, ValidatorFn, Validators} from "@angular/forms";
import {TaskData, TaskModule} from "../../_Module/TaskListModule";
import {HttpParams} from "@angular/common/http";
import {CronJobData, CronJobModule} from "../../_Module/CronJobModule";

@Component({
  selector: 'app-cron-jobs-create',
  templateUrl: './cron-jobs-create.component.html',
  styleUrls: ['./cron-jobs-create.component.sass']
})
export class CronJobsCreateComponent implements OnInit {
  pid:string;
  id:string;
  isShowForm:boolean = false;
  myForm:FormGroup;
  taskOption:TaskModule[];
  cronConfig:any;
  minTime:any;
  isEdit:boolean;
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
          this.id = obj['id'] || null;
          this.isEdit = (this.id) ? true : false;
          this.getDetail();
        })
      }else{
        this.getDetail();
      }
    });

    this.getOption();
    this.minTime = new Date();

    this.cronConfig = {
      quartz: false,
      allowMultiple: true
    }
  }

  selectType(event){
    this.myForm.get('condition').setValue('');
  }

  submitForm(){
    this.tools.checkForm(this.myForm);

    if(this.myForm.status == 'VALID'){
      let formData = new HttpParams();
      formData = formData.set('pid', this.pid);
      formData = formData.set('task_id', this.myForm.get('task').value);
      formData = formData.set('name', this.myForm.get('name').value);
      formData = formData.set('description', this.myForm.get('description').value);
      formData = formData.set('type', this.myForm.get('type').value);
      if(this.myForm.get('type').value == 100){
        let condition = new Date(this.myForm.get('condition').value).getTime();
        formData = formData.set('condition', condition.toString());
      }else{
        formData = formData.set('condition', this.myForm.get('condition').value);
      }

      if(this.id){
        formData = formData.set('id', this.id);
        this.apiService.updateCronJob(formData, this.pid).subscribe((res) => {
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
        this.apiService.createCronJob(formData, this.pid).subscribe((res) => {
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
    }else{
      if(this.myForm.get('condition').value == '' || this.myForm.get('condition').value == null){
        this.snackBar.open('条件选项不能为空！', '', {
          duration: 3000,
          panelClass: ['error-toaster']
        })
      }else{
        this.snackBar.open('表单验证未通过！', '', {
          duration: 3000,
          panelClass: ['error-toaster']
        })
      }

    }
  }

  private getDetail(){
    if(this.id){
       let formData = new HttpParams();
       formData = formData.set('id', this.id);
       this.apiService.getCronJob(formData, this.pid).subscribe((res)=>{
         if(res['status'] == 200){
           let option = new CronJobData<CronJobModule>(res['data']);

           this.initForm(option.option[0]);
         }else{
           this.tools.StatusError(res);
         }
       }, (error) => {
         this.tools.ServerError(error);
       })
    }else{
      this.initForm();
    }
  }

  private initForm(data?:any){
    let defaultCondition:string = '';
    if(data){
      defaultCondition = (data.typeCode && data.typeCode == 100) ? new Date(data.condition) : data.condition;

    }
    this.myForm = new FormGroup({
      name: new FormControl((data && data.name) ? data.name : '', Validators.required),
      description: new FormControl((data && data.description) ? data.description : '', Validators.required),
      type: new FormControl((data && data.type) ? data.typeCode : '', Validators.required),
      task: new FormControl((data && data.taskId) ? data.taskId : '', Validators.required),
      condition: new FormControl( defaultCondition, Validators.required)
    });
    this.isShowForm = true;
  }

  private getOption(){
    let formData = new HttpParams();
    formData = formData.set('pid', this.pid);
    this.apiService.getTask(formData, this.pid).subscribe((res) => {
      if(res['status'] == 200){
        let option = new TaskData<TaskModule>(res['data']);
        this.taskOption = option.option;
      }else{
        this.tools.StatusError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }
}
