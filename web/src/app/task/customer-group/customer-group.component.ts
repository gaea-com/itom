import {Component, OnInit, ViewChild} from '@angular/core';
import {ActivatedRoute} from "@angular/router";
import {ToolsService} from "../../_Service/tools.service";
import {ApiService} from "../../_Service/api.service";
import {HttpParams} from "@angular/common/http";
import {CustomerGroupData, CustomerGroupModule} from "../../_Module/CustomerGroupModule";
import {MatDialog, MatPaginator, MatSnackBar, MatTable, MatTableDataSource} from "@angular/material";
import {InstanceModule} from "../../_Module/InstanceModule";
import {ContainerModule} from "../../_Module/ContainerModule";
import {PopComponent} from "../../pop/pop.component";

@Component({
  selector: 'app-customer-group',
  templateUrl: './customer-group.component.html',
  styleUrls: ['./customer-group.component.sass']
})
export class CustomerGroupComponent implements OnInit {
  insParams:string;
  dockerParams:string;
  pid:string;
  dataSource = new MatTableDataSource<CustomerGroupModule>([]);
  displayedColumns = [ 'name', 'description', 'type', 'num', 'operate'];
  @ViewChild('table', {static:true}) table: MatTable<Element>;
  @ViewChild(MatPaginator, {static:true}) paginator: MatPaginator;
  constructor(private tools:ToolsService,
              private apiService:ApiService,
              public dialog: MatDialog,
              public snackBar: MatSnackBar,
              private route: ActivatedRoute) { }

  ngOnInit() {
    this.route.parent.params.subscribe(params => {
      if(params.params){
        this.tools.parseParams(params.params, (obj) => {
          this.pid = obj['pid'];
        })
      }
    });
    this.getGroupList();
    this.insParams = encodeURI('type=100');
    this.dockerParams = encodeURI('type=200');
    this.dataSource.paginator = this.paginator;
  }

  pause(element){
    if(element.server.length == 0){
      this.snackBar.open('该自定义组下没有容器！', '',{
        duration: 1000,
        panelClass: ['error-toaster']
      })
      return false;
    }

    let ids = [];
    let names = [];

    element.server.forEach(item => {
      ids.push(item.id);
      names.push(item.name);
    });

    let dialogRef = this.dialog.open(PopComponent, {
      height: '60%',
      width: '50%',
      disableClose: true,
      autoFocus: false,
      data: {
        ids: ids,
        name: names,
        type: 'stopContainer',
        title: null,
        msg: '确认要关闭以下容器吗？',
        project_id: this.pid,
      }
    });
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
          type: 'deleteCustomerGroup',
          title: '自定义组',
          project_id: this.pid
        }
      });

      dialogRef.afterClosed().subscribe(result => {
        if (result == 'done') {
          this.getGroupList();
        }
      });
    }
  }

  private getGroupList(){
    let formData = new HttpParams();
    formData = formData.set('pid', this.pid);
    this.apiService.getCustomerGroup(formData, this.pid).subscribe((res) => {
      if(res['status'] == 200){
        let option = new CustomerGroupData<CustomerGroupModule>(res['data']);
        this.dataSource.data = option.option;
      }else{
        this.tools.StatusError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }
}
