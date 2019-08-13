import {Component, OnInit, ViewChild} from '@angular/core';
import {Location} from "@angular/common";
import {ActivatedRoute} from "@angular/router";
import {ToolsService} from "../../_Service/tools.service";
import {ApiService} from "../../_Service/api.service";
import {MatDialog, MatPaginator, MatSnackBar, MatTable, MatTableDataSource} from "@angular/material";
import {HttpParams} from "@angular/common/http";
import {TaskData, TaskModule} from "../../_Module/TaskListModule";
import {ScriptModule} from "../../_Module/ScriptModule";
import {animate, state, style, transition, trigger} from "@angular/animations";
import {PopComponent} from "../../pop/pop.component";

@Component({
  selector: 'app-task',
  templateUrl: './task.component.html',
  styleUrls: ['./task.component.sass'],
  animations: [
    trigger('detailExpand', [
      state('collapsed', style({height: '0px', minHeight: '0'})),
      state('expanded', style({height: '*'})),
      transition('expanded <=> collapsed', animate('225ms cubic-bezier(0.4, 0.0, 0.2, 1)')),
    ]),
  ]
})
export class TaskComponent implements OnInit {
  pid:string;
  dataSource = new MatTableDataSource<ScriptModule|any>([]);
  columnsToDisplay = ['name', 'description', 'operate'];
  expandedElement: ScriptModule | null;
  @ViewChild('table', {static:true}) table: MatTable<Element>;
  @ViewChild(MatPaginator, {static:true}) paginator: MatPaginator;
  constructor(private tools:ToolsService,
              private apiService:ApiService,
              public snackBar: MatSnackBar,
              public dialog: MatDialog,
              private route: ActivatedRoute) { }

  ngOnInit() {
    this.route.parent.params.subscribe(params => {
      if(params.params){
        this.tools.parseParams(params.params, (obj) => {
          this.pid = obj['pid'];
          this.getTaskList();
        })
      }
    });
    this.dataSource.paginator = this.paginator;
  }

  delete(element){
    if(element){
      let dialogRef = this.dialog.open(PopComponent, {
        height: '60%',
        width: '50%',
        disableClose: true,
        autoFocus: false,
        data: {
          ids: [element.id],
          name: [element.name],
          type: 'deleteTask',
          title: '任务',
          project_id: this.pid
        }
      });

      dialogRef.afterClosed().subscribe(result => {
        if (result == 'done') {
          this.getTaskList();
        }
      });
    }else{
      this.snackBar.open('您暂时无法删除这条命令', '',{
        duration: 1000,
        panelClass: ['error-toaster']
      });
    }
  }

  run(element, idx){
    let formData = new HttpParams();
    formData = formData.set('task_id', element.id);
    formData = formData.set('project_id', this.pid);
    formData = formData.set('order_sort', idx+1);

    this.apiService.runTask(formData, this.pid).subscribe((res) => {
      if(res['status'] == 200){
        this.tools.StatusSuccess(res, '任务已经提交请耐心等待');
      }else{
        this.tools.StatusError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    });
  }

  private getTaskList(){
    let formData = new HttpParams();
    formData = formData.set('pid', this.pid);
    this.apiService.getTask(formData, this.pid).subscribe((res) => {
      if(res['status'] == 200){
        let option = new TaskData<TaskModule>(res['data']);
        this.dataSource.data = option.option;
      }else{
        this.tools.StatusError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }
}
