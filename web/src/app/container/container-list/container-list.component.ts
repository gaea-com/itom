import {Component, OnDestroy, OnInit, ViewChild} from '@angular/core';
import {ChatService} from "../../_Service/chat.service";
import {ApiService} from "../../_Service/api.service";
import {ToolsService} from "../../_Service/tools.service";
import {ActivatedRoute, Router} from "@angular/router";
import {MatDialog, MatPaginator, MatSnackBar, MatTable, MatTableDataSource} from "@angular/material";
import {HttpParams} from "@angular/common/http";
import {InstanceModule} from "../../_Module/InstanceModule";
import {SelectionModel} from "@angular/cdk/collections";
import {Subscription} from "rxjs/Subscription";
import {MessageCenterService} from "../../_Service/message-center.service";
import {ContainerData, ContainerModule} from "../../_Module/ContainerModule";
import {PopComponent} from "../../pop/pop.component";
import {FormControl, FormGroup, Validators} from "@angular/forms";
import {OvserveFileService} from "../../_Service/ovserve-file.service";
import {PopUploadToDockerComponent} from "../pop-upload-to-docker/pop-upload-to-docker.component";
import {SendCmdToInstanceComponent} from "../../instance/send-cmd-to-instance/send-cmd-to-instance.component";
import {PopWebShellComponent} from "../pop-web-shell/pop-web-shell.component";
import {StopDockerData, StopDockerModule} from "../../_Module/StopDockerModule";
import {OvserveWSService} from "../../_Service/ovserve-ws.service";

@Component({
  selector: 'app-container-list',
  templateUrl: './container-list.component.html',
  styleUrls: ['./container-list.component.sass']
})
export class ContainerListComponent implements OnInit, OnDestroy {
  pid:string;
  sid:string;
  cid:string;
  server_name:string;
  serverIp:string;
  @ViewChild('table', {static:true}) table: MatTable<any>;
  @ViewChild(MatPaginator, {static:true}) paginator: MatPaginator;

  displayedColumns: string[] = ['checkbox', 'name', 'description', 'status', 'IP', 'image', 'operate'];
  displayedStopColumns: string[] = ['name', 'closeTime', 'operate'];
  dataSource = new MatTableDataSource<ContainerModule>([]);
  dataStopSource = new MatTableDataSource<StopDockerModule>([]);
  selection = new SelectionModel<ContainerModule>(true, []);

  cmdForm:FormGroup;
  isShowStopContainerList:boolean = false;

  private subscription: Subscription;
  constructor(private route: ActivatedRoute,
              private router: Router,
              private tools: ToolsService,
              private apiService:ApiService,
              public dialog: MatDialog,
              public snackBar: MatSnackBar,
              private _messageService: OvserveWSService) {
    this.subscription = _messageService.rp$.subscribe((res) => {
      this.getContainerList(res);
    })
  }

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
          this.sid = obj['sid'];
          this.server_name = obj['name'];
          this.cid = obj['cid'];
          this.serverIp = obj['ip'];
          this.getContainerList();
        })
      }else{
        this.getContainerList();
      }
      this.dataSource.paginator = this.paginator;
    });

    this.cmdForm = new FormGroup({
      cmd: new FormControl('', Validators.required)
    });
  }

  ngOnDestroy() {
    this.subscription.unsubscribe();
  }

  isAllSelected() {
    const numSelected = this.selection.selected.length;
    const numRows = this.dataSource.data.length;
    return numSelected === numRows;
  }

  /** Selects all rows if they are not all selected; otherwise clear selection. */
  masterToggle() {
    this.isAllSelected() ?
      this.selection.clear() :
      this.dataSource.data.forEach(row => this.selection.select(row));
  }

  setContainer(element, type){
    let title = (type == 'upload') ? '上传文件到容器' + element.name : '从容器' + element.name + '下载文件';

    if(element){
      let dialogRef = this.dialog.open(PopUploadToDockerComponent, {
        height: '60%',
        width: '50%',
        disableClose: true,
        autoFocus: false,
        data: {
          ip: element.IP,
          code: element.code,
          title: title,
          type: type
        }
      });

      dialogRef.afterClosed().subscribe(() => {
        this.selection.clear();
      });
    }

    return false;
  }

  sendCmd(){
    if(this.selection.selected.length == 0){
      this.snackBar.open('请选择容器', '',{
        duration: 1000,
        panelClass: ['error-toaster']
      })
      return false;
    }
    let ids = this.selection.selected.map(item => item.id);
    let dialogRef = this.dialog.open(SendCmdToInstanceComponent, {
      height: '60%',
      width: '50%',
      disableClose: true,
      autoFocus: false,
      data: {
        request: ids,
        pid: this.pid,
        type: 200
      }
    });

    dialogRef.afterClosed().subscribe(() => {
      this.selection.clear();
    });
  }

  runContainer(){
    if(this.pid && this.sid && this.cid){
      let item = {}
      item['project_id'] = this.pid;
      item['instance_id'] = this.sid;
      item['cloud_type'] = 'gaea';
      item['compose_id'] = this.cid;

      let containerArr = [];
      containerArr[0] = item;
      let formData = new HttpParams();
      formData = formData.set('pid', this.pid);
      formData = formData.set('request', JSON.stringify(containerArr));

      this.apiService.runContainer(formData, this.pid).subscribe((res) => {
        if(res['status'] == 200){
          this.tools.StatusSuccess(res, '任务已经提交请耐心等待');
        }else{
          this.tools.StatusError(res);
        }
      }, (error) => {
        this.tools.ServerError(error);
      })
    }else{
      this.snackBar.open('缺少实例ID', '',{
        duration: 3000,
        panelClass: ['error-toaster']
      })
    }
  }

  createTab(element){
    if(element){
      let dialogRef = this.dialog.open(PopWebShellComponent, {
        height: '60%',
        width: '50%',
        disableClose: true,
        autoFocus: false,
        data: {
          ip: element.IP,
          id: element.code
        }
      });

      dialogRef.afterClosed().subscribe(result => {
        if (result == 'done') {
          this.getContainerList();
        }
        this.selection.clear();
      });
    }
  }

  closeContainer(){
    if(this.selection.selected.length == 0){
      this.snackBar.open('请选择待关闭容器', '',{
        duration: 1000,
        panelClass: ['error-toaster']
      })
      return false;
    }

    let ids = [];
    let names = [];
    this.selection.selected.forEach(item => {
      ids.push(item.id);
      names.push(item.name);
    })

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

    dialogRef.afterClosed().subscribe(result => {
      if (result == 'done') {
        this.getContainerList();
      }
      this.selection.clear();
    });
  }

  updateContainer(){
    if(this.sid){
      let formData = new HttpParams();
      formData = formData.set('project_id', this.pid);
      formData = formData.set('instance_id', this.sid);
      formData = formData.set('cloud_type', 'gaea');

      this.apiService.updateContainer(formData, this.pid).subscribe((res) => {
        if(res['status'] == 200){
          this.tools.StatusSuccess(res, '任务已经提交，请耐心等待～');
        }else{
          this.tools.StatusError(res);
        }
      }, (error) => {
        this.tools.ServerError(error);
      })
    }else{
      this.snackBar.open('缺少实例ID', '',{
        duration: 3000,
        panelClass: ['error-toaster']
      })
    }
  }

  showDeleteDocker(event){
    let val = event.index;
    if(!this.serverIp){
      this.snackBar.open('缺少实例IP', '',{
        duration: 3000,
        panelClass: ['error-toaster']
      });
      return false;
    }

    if(val){
      let formData = new HttpParams();
      formData = formData.set('ip', this.serverIp);
      this.apiService.getStopContainer(formData, this.pid).subscribe((res) => {
        if(res['status'] == 200){
          let option = new StopDockerData<StopDockerModule>(res['data']);
          this.dataStopSource.data = option.option
        }else{
          this.tools.StatusError(res);
        }
      }, (error) => {
        this.tools.ServerError(error);
      });
    }
  }


  private getContainerList(type?){
    if(!type || type == 'container'){
      let formData = new HttpParams();
      formData = formData.set('project_id', this.pid);
      if(this.sid){
        formData = formData.set('instance_id', this.sid);
      }else{
        if(this.displayedColumns[2] != 'server_name'){
          this.displayedColumns.splice(2, 0, 'server_name');
        }
      }
      this.apiService.getContainer(formData, this.pid).subscribe((res) => {
        if(res['status'] == 200){
          let option = new ContainerData<ContainerModule>(res['data']);
          this.dataSource.data = option.option;
          // this.dataSource.paginator = this.paginator;
        }else{
          this.tools.StatusError(res);
        }
      }, (error) => {
        this.tools.ServerError(error);
      })
    }
  }
}
