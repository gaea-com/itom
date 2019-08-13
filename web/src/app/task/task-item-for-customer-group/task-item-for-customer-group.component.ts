import {Component, Input, OnInit} from '@angular/core';
import {CustomerGroupData, CustomerGroupModule} from "../../_Module/CustomerGroupModule";
import {HttpParams} from "@angular/common/http";
import {ToolsService} from "../../_Service/tools.service";
import {ApiService} from "../../_Service/api.service";
import {FormGroup} from "@angular/forms";

@Component({
  selector: 'app-task-item-for-customer-group',
  templateUrl: './task-item-for-customer-group.component.html',
  styleUrls: ['./task-item-for-customer-group.component.sass']
})
export class TaskItemForCustomerGroupComponent implements OnInit {
  @Input('pid') pid:string;
  @Input('defaultValue') defaultValue:string;
  @Input('myForm') myForm:FormGroup;
  @Input('type') type:number;
  @Input('status') status:string;
  scriptOption:CustomerGroupModule[] = [];
  params:string;
  url:string;
  constructor(private tools:ToolsService,
              private apiService:ApiService,) { }

  ngOnInit() {
    this.url = (this.status == 'edit') ? '../../createCustomerGroup' : '../createCustomerGroup';

    this.params = encodeURI('type=' + this.type);
    let formData = new HttpParams();
    formData = formData.set('pid', this.pid);
    formData = formData.set('type', this.type.toString());
    this.apiService.getCustomerGroup(formData, this.pid).subscribe((res) => {
      if(res['status'] == 200){
        let option = new CustomerGroupData<CustomerGroupModule>(res['data']);
        this.scriptOption = option.option;

        if(this.defaultValue && this.defaultValue['group']){
          if(this.defaultValue['group'].length == 1){
            let groupItem = this.defaultValue['group'][0];
            this.scriptOption.forEach(prop => {
              if(prop.id == groupItem.id){
                this.myForm.get('result').setValue(prop);
              }
            });
          }else{
            // console.log('自定义组多出来了！');
            // console.log(this.defaultValue['group']);
          }
        }
      }else{
        this.tools.StatusError(res);
      }
    }, (error)=> {
      this.tools.ServerError(error);
    });
  }
}
