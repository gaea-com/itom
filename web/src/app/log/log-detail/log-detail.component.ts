import {Component, Input, OnInit, ViewChild} from '@angular/core';
import {ActivatedRoute} from "@angular/router";
import {ToolsService} from "../../_Service/tools.service";
import {ApiService} from "../../_Service/api.service";
import {MatDialog, MatSnackBar, MatTable, MatTableDataSource} from "@angular/material";
import {HttpParams} from "@angular/common/http";
import {LogData, LogDetailData, LogModule} from "../../_Module/LogModule";
import {PopLogDetailComponent} from "../pop-log-detail/pop-log-detail.component";

@Component({
  selector: 'app-log-detail',
  templateUrl: './log-detail.component.html',
  styleUrls: ['./log-detail.component.sass']
})
export class LogDetailComponent implements OnInit {
  taskId:string;
  displayedColumns: string[] = ['serverName', 'startTime', 'endTime', 'detail'];
  dataSource = new MatTableDataSource<LogModule>([]);
  @ViewChild('table', {static:true}) table: MatTable<LogModule>;
  constructor(private tools:ToolsService,
              private apiService:ApiService,
              public snackBar: MatSnackBar,
              public dialog: MatDialog,
              private route: ActivatedRoute) { }

  ngOnInit() {
    this.route.params.subscribe(params => {
      if(params.params){
        this.taskId = params.params;
        this.getLogList(this.taskId);
      }
    });
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

  private getLogList(taskId){

    let formData = new HttpParams();
    formData = formData.set('task_id', taskId);
    this.apiService.getLog(formData).subscribe((res) => {
      if(res['status'] == 200){
        let option = new LogDetailData<LogModule>(res['data']);

        this.dataSource.data = option.option;
      }else{
        this.tools.StatusError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }
}
