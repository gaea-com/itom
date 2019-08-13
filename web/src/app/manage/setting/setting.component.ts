import {Component, OnInit, ViewChild} from '@angular/core';
import {FormBuilder, FormControl, FormGroup, Validator, Validators} from "@angular/forms";
import {ActivatedRoute} from "@angular/router";
import {ToolsService} from "../../_Service/tools.service";
import {ApiService} from "../../_Service/api.service";
import {HttpParams} from "@angular/common/http";
import {MatPaginator, MatSnackBar, MatTable, MatTableDataSource} from "@angular/material";
import {HubData, HubModule} from "../../_Module/HubModule";
import {debounceTime} from "rxjs/operators";

@Component({
  selector: 'app-setting',
  templateUrl: './setting.component.html',
  styleUrls: ['./setting.component.sass']
})
export class SettingComponent implements OnInit {
  status:boolean = false;
  loading:boolean = true;
  canSave:boolean = true;
  myForm:FormGroup;
  searchForm:FormGroup;
  hubList:HubModule[] = [];
  endCursor:string;
  isSearching:boolean = false;

  constructor(private route: ActivatedRoute,
              public tools:ToolsService,
              public snackBar: MatSnackBar,
              private apiService:ApiService) { }

  ngOnInit() {
    this.apiService.checkHub().subscribe((res) => {
      if(res['status'] == 200){
        this.loading = false;
        this.status = false;
      }else{
        this.loading = false;
        this.status = true;
        this.initForm();
      }
    }, (error) => {
      this.tools.ServerError(error);
    });

    this.myForm = new FormGroup({
      address: new FormControl('', [
        Validators.required,
        Validators.pattern('^(http|https):\/\/[^ "]+[^\/]$')
        ]),
      username: new FormControl('', Validators.required),
      password: new FormControl('', Validators.required)
    });

    this.myForm.valueChanges.subscribe((res) => {
      this.canSave = true;
    });
  }

  test(){
    this.tools.checkForm(this.myForm);

    if(this.myForm.status == "VALID"){
      let url = encodeURI(this.myForm.get('address').value);
      let formData = new HttpParams();
      formData = formData.set('hub_url', btoa(url));
      formData = formData.set('hub_user', this.myForm.get('username').value);
      formData = formData.set('hub_password', this.myForm.get('password').value);

      this.apiService.testHub(formData).subscribe((res) => {
        if(res['status'] == 200){
          this.canSave = false;
          this.snackBar.open('测试成功，请保存！', '', {
            duration: 1000,
            panelClass: ['success-toaster']
          })
        }else{
          this.tools.StatusError(res);
        }
      }, (error) => {
        this.tools.ServerError(error);
      })
    }
  }

  getDetail(element){
    element.status = !element.status;
  }

  getTagList(event){

  }

  private initForm(){
    this.searchForm = new FormGroup({
      search: new FormControl('')
    })
  }

  onScrollDown(list){
    let formData = new HttpParams();
    formData = formData.set('img_name', this.endCursor);
    formData = formData.set('cover', '0');
    formData = formData.set('num', '30');
    this.getHubList(formData);
  }

  search(){
    let formData = new HttpParams();
    formData = formData.set('img_name', this.searchForm.get('search').value);
    formData = formData.set('cover', '1');
    formData = formData.set('num', '100');
    this.hubList = [];
    this.isSearching = true;
    this.getHubList(formData)
  }

  editHub(){
    this.status = false;
  }

  submitForm(){
    this.tools.checkForm(this.myForm);

    if(this.myForm.status == "VALID"){
      let url = encodeURI(this.myForm.get('address').value);
      let formData = new HttpParams();
      formData = formData.set('hub_url', btoa(url));
      formData = formData.set('hub_user', this.myForm.get('username').value);
      formData = formData.set('hub_password', this.myForm.get('password').value);

      this.apiService.saveHubConfig(formData).subscribe((res) => {
        if(res['status'] == 200){
          this.status = true;
          this.tools.StatusSuccess(res, '保存成功');
          this.initForm();
        }else{
          this.tools.StatusError(res);
        }
      }, (error) => {
        this.tools.ServerError(error);
      })
    }
  }

  private getHubList(formData){
    this.apiService.getHubList(formData).subscribe((res) => {
      if(res['status'] == 200){
        let option = new HubData<HubModule>(res['data']);
        if(option.option[option.option.length-1].image != this.endCursor){
          this.hubList= this.hubList.concat(option.option);
          this.endCursor = this.hubList[this.hubList.length-1].image;
        }else{
          this.hubList = option.option;
        }
      }else{
        this.tools.StatusError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }
}
