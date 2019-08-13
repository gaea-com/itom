import {Component, OnInit, ViewChild} from '@angular/core';
import {ProjectData, ProjectModule} from "../../_Module/ProjectModule";
import {ApiService} from "../../_Service/api.service";
import {ToolsService} from "../../_Service/tools.service";
import {PermData, PermModule} from "../../_Module/PermModule";
import {MatDialog, MatPaginator, MatTable, MatTableDataSource} from "@angular/material";
import {PopPermComponent} from "../pop-perm/pop-perm.component";
import {HttpParams} from "@angular/common/http";

@Component({
  selector: 'app-manage-list',
  templateUrl: './manage-list.component.html',
  styleUrls: ['./manage-list.component.sass']
})
export class ManageListComponent implements OnInit {
  projectList:ProjectModule[];
  displayedColumns: string[] = ['project', 'user', 'createTime', 'operate'];
  dataSource = new MatTableDataSource<PermModule>([]);
  projectSel:string
  @ViewChild('table', {static:true}) table: MatTable<PermModule>;
  @ViewChild(MatPaginator, {static:true}) paginator: MatPaginator;
  constructor(public apiService:ApiService,
              public tools:ToolsService,
              public dialog: MatDialog) { }

  ngOnInit() {
    this.getProjectList()
    this.dataSource.paginator = this.paginator;
  }

  selectedProject(event){
    let val = event.value;
    if(val){
      this.getUserProject(val);
    }
  }

  perm(){
    let dialogRef = this.dialog.open(PopPermComponent, {
      height: '60%',
      width: '50%',
      disableClose: true,
      autoFocus: false,
      data: {
        id: this.projectSel
      }
    });

    dialogRef.afterClosed().subscribe(result => {
      if(result){
        this.getUserProject(this.projectSel);
      }
    });

  }

  delete(element){
    let formData = new HttpParams();
    formData = formData.set('uid', element.userId);
    formData = formData.set('pid', element.projectcode);
    this.apiService.deletePerm(formData).subscribe((res) => {
      if(res['status'] == 200){
        let val = this.projectSel;
        this.getUserProject(val);
        this.tools.StatusSuccess(res, '移除权限成功');
      }else{
        this.tools.StatusError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }

  private getProjectList(){
    this.apiService.getProject({}).subscribe((res) => {
      if(res['status'] == 200){
        let option = new ProjectData<ProjectModule>(res['data']);
        this.projectList = option.option;
      }else{
        this.tools.StatusError(res)
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }

  private getUserProject(pid){
    this.apiService.getUserProject({
      "pid": pid
    }).subscribe((res) => {
      let option = new PermData<PermModule>(res['data']);
      this.dataSource.data = option.option;
    }, (error) => {
      this.tools.ServerError(error);
    })
  }
}
