import {Component, OnInit, ViewChild} from '@angular/core';
import {FormControl, FormGroup, Validators} from "@angular/forms";
import {InstanceData, InstanceModule} from "../../_Module/InstanceModule";
import {ActivatedRoute} from "@angular/router";
import {Location} from "@angular/common";
import {MatDialog, MatPaginator, MatSnackBar, MatTable, MatTableDataSource, PageEvent} from "@angular/material";
import {ToolsService} from "../../_Service/tools.service";
import {ApiService} from "../../_Service/api.service";
import {HttpParams} from "@angular/common/http";
import {TopologyData} from "../../_Module/TopologyModule";
import {UserData, UserModule} from "../../_Module/UserModule";
import {LogData, LogModule} from "../../_Module/LogModule";
import {PopComponent} from "../../pop/pop.component";
import {PopLogDetailComponent} from "../pop-log-detail/pop-log-detail.component";

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.sass']
})
export class LogComponent implements OnInit {
  myForm:FormGroup;
  instanceOption: InstanceModule[];
  userOption;
  pid:string;
  displayedColumns: string[] = ['operate', 'taskId', 'serverName', 'startTime', 'endTime', 'userName'];
  dataSource = new MatTableDataSource<LogModule>([]);
  @ViewChild('table', {static:true}) table: MatTable<LogModule>;
  @ViewChild(MatPaginator, {static:true}) paginator: MatPaginator;

  length = 0;
  pageSize = 10;
  pageSizeOptions: number[] = [5, 10, 25, 100];
  isShowTable:boolean = false;
  nowDate:any = new Date();

  constructor(private tools:ToolsService,
              private apiService:ApiService,
              public snackBar: MatSnackBar,
              public dialog: MatDialog,
              private route: ActivatedRoute) {
    this.route.parent.params.subscribe(params => {
      if(params.params){
        this.tools.parseParams(params.params, (obj) => {
          this.pid = obj['pid'];
          this.getInstanceList();
          this.getUserList();
        })
      }
    });
  }

  ngOnInit() {
    // this.dataSource.paginator = this.paginator;
    this.myForm = new FormGroup({
      type: new FormControl('', Validators.required),
      instance: new FormControl(''),
      user: new FormControl(''),
      date: new FormControl([new Date(this.nowDate - 86400000*3), this.nowDate])
    });

  }

  getNext(event){
    this.submit(event.pageIndex+1, event.pageSize);
    return false;
  }

  selectType(event){
    if(event == 'instance'){
      this.displayedColumns = ['operate', 'taskId', 'serverName', 'startTime', 'endTime', 'userName']
    }else{
      this.displayedColumns = ['startTime', 'taskId', 'endTime', 'userName', 'detail']
    }

    return false;
  }

  submit(page?, count?){
    let typeCtl = this.myForm.get('type');
    typeCtl.markAsUntouched();
    typeCtl.updateValueAndValidity();

    if(!page && !count){
      this.paginator.firstPage();
    }

    if(this.myForm.status == 'VALID'){
      let currentPage = (page) ? page.toString() : '1';
      let totalPage = (count) ? count.toString() : this.pageSize.toString();
      let formData = new HttpParams();
      formData = formData.set('id', this.pid);
      formData = formData.set('page', currentPage);
      formData = formData.set('count', totalPage);
      formData = formData.set('type', this.myForm.get('type').value);
      formData = formData.set('instance_id', this.myForm.get('instance').value);
      formData = formData.set('cloud_type', 'gaea');
      if(this.myForm.get('type').value == 'instance'){
        formData = formData.set('uid', this.myForm.get('user').value);
      }else{
        let d = this.myForm.get('date').value;
        let startDate = new Date(d[0]).getTime();
        let endDate = new Date(d[1]).getTime();

        if(endDate - startDate > 86400000*3){
          this.snackBar.open('日志查询跨度间隔不能超过三天', '', {
            duration: 1000,
            panelClass: ['error-toaster']
          });
          return false;
        }

        formData = formData.set('start_date', startDate.toString());
        formData = formData.set('end_date', endDate.toString());
      }


      this.apiService.getLog(formData).subscribe((res) => {
        if(res['status'] == 200){
          let option = new LogData<LogModule>(res['data']);

          this.dataSource.data = option.option;
          if(!this.length){
            this.length = option.length;
          }
        }else{
          this.tools.StatusError(res);
        }
      }, (error) => {
        this.tools.ServerError(error);
      })
    }else{
      this.snackBar.open('表单验证未通过', '', {
        duration: 1000,
        panelClass: ['error-toaster']
      });
    }
  }

  checkDetail(element){
    if(element){
      let dialogRef = this.dialog.open(PopLogDetailComponent, {
        height: '60%',
        width: '50%',
        disableClose: true,
        autoFocus: false,
        data: {
          request: [element.request],
          response: [element.response]
        }
      });
    }
  }

  private getInstanceList(){
    let formData = new HttpParams();
    formData = formData.set('pid', this.pid);
    this.apiService.getTopologyList(formData, this.pid).subscribe((res) => {
      if(res['status'] == 200){
        let option = new InstanceData<InstanceModule>(res['data']);
        this.instanceOption = option.option;
      }else{
        this.tools.StatusError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }

  private getUserList(){
    this.apiService.getProjectUser(this.pid).subscribe((res) => {
      if(res['status'] == 200){
        let option = new UserData<UserModule>(res['data']);
        this.userOption = option.option;
      }else{
        this.tools.StatusError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }
}
