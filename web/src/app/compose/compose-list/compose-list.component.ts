import {Component, OnInit, ViewChild} from '@angular/core';
import {MatDialog, MatPaginator, MatTable, MatTableDataSource} from "@angular/material";
import {PopComponent} from "../../pop/pop.component";
import {AddComposeComponent} from "../add-compose/add-compose.component";
import {ToolsService} from "../../_Service/tools.service";
import {ActivatedRoute} from "@angular/router";
import {ApiService} from "../../_Service/api.service";
import {ProjectModule} from "../../_Module/ProjectModule";
import {ComposeData, ComposeModule} from "../../_Module/ComposeModule";
import {HttpParams} from "@angular/common/http";

@Component({
  selector: 'app-compose-list',
  templateUrl: './compose-list.component.html',
  styleUrls: ['./compose-list.component.sass']
})
export class ComposeListComponent implements OnInit {
  pid:string;
  dataSource = new MatTableDataSource<ComposeModule>([]);
  displayedColumns = [ 'name', 'imageList', 'description', 'operate'];
  @ViewChild('table', {static:true}) table: MatTable<Element>;
  @ViewChild(MatPaginator, {static:true}) paginator: MatPaginator;
  constructor(public dialog: MatDialog,
              private route: ActivatedRoute,
              private tools: ToolsService,
              private apiService: ApiService) { }

  ngOnInit() {
    this.route.parent.params.subscribe(params => {
      if(params.params){
        this.tools.parseParams(params.params, (obj) => {
          this.pid = obj['pid'];
          this.getComposeList();
        })
      }
    });
    this.dataSource.paginator = this.paginator;
  }

  addCompose(element?){
    let dialogRef = this.dialog.open(AddComposeComponent, {
      height: '60%',
      width: '50%',
      disableClose: true,
      autoFocus: false,
      data: {
        name: (element) ? element['name'] : '',
        description: (element) ? element['description'] : '',
        id: (element) ? element['id'] : '',
        project_id: this.pid,
        image_list: (element) ? element['detail'] : []
      }
    });

    dialogRef.afterClosed().subscribe(result => {
      if(result == 'done'){
        this.getComposeList();
      }
    });
  }

  swithStatus(element){
    let formData = new HttpParams();
    if(element['status'] == 200){
      formData = formData.set('type', 'disable');
    }else{
      formData = formData.set('type', 'enabled');
    }

    this.apiService.statusCompose(formData, element.id, this.pid).subscribe((res) => {
      if(res['status'] == 200){
        element['status'] = (element['status'] == 200) ? 100 : 200;
      }else{
        this.tools.ServerError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }

  private getComposeList(){
    let formData = new HttpParams();
    formData = formData.append('pid', this.pid);
    this.apiService.getCompose(formData, this.pid).subscribe((res) => {
      if(res['status'] == 200){
        let option = new ComposeData<ComposeModule>(res['data']);
        this.dataSource.data = option.data;
      }else{
        this.tools.StatusError(res)
      }
    },(error) => {
      this.tools.ServerError(error);
    })
  }
}
