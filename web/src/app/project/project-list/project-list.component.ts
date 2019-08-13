import {Component, OnInit, ViewChild} from '@angular/core';
import {ApiService} from "../../_Service/api.service";
import {MatDialog, MatPaginator, MatSort, MatTable, MatTableDataSource} from "@angular/material";
import {ProjectData, ProjectModule} from "../../_Module/ProjectModule";
import {ToolsService} from "../../_Service/tools.service";
import {ActivatedRoute, Router} from "@angular/router";
import {PopComponent} from "../../pop/pop.component";

@Component({
  selector: 'app-project-list',
  templateUrl: './project-list.component.html',
  styleUrls: ['./project-list.component.sass']
})
export class ProjectListComponent implements OnInit {
  dataSource = new MatTableDataSource<ProjectModule>([]);
  displayedColumns = [ 'name', 'description', 'operate'];
  @ViewChild('table', {static:true}) table: MatTable<Element>;
  @ViewChild(MatPaginator, {static:true}) paginator: MatPaginator;
  constructor(public tools:ToolsService,
              private route: ActivatedRoute,
              private router: Router,
              private apiService: ApiService,
              public dialog: MatDialog) { }

  ngOnInit() {
    this.getProjectList();
    this.dataSource.paginator = this.paginator;
  }


  editProject(element){
    this.router.navigate(['../projectEdit', element.id], {relativeTo: this.route})
  }

  deleteProject(element, idx){
    let id = element.id;
    let dialogRef = this.dialog.open(PopComponent, {
      height: '60%',
      width: '50%',
      disableClose: true,
      autoFocus: false,
      data: {
        ids: [element.id],
        name: [element.name],
        type: 'project',
        title: '项目',
        project_id: id
      }
    });

    dialogRef.afterClosed().subscribe(result => {
      if(result == 'done'){
        this.getProjectList();
      }
    });
  }

  private getProjectList(){
    this.apiService.getProject({}).subscribe((res) => {
      if(res['status'] == 200){
        let option = new ProjectData<ProjectModule>(res['data']);
        this.dataSource.data = option['option'];
      }else{
        this.tools.StatusError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }
}
