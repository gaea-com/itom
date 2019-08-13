import {Component, OnDestroy, OnInit, ViewChild} from '@angular/core';
import {ChatService} from "../../_Service/chat.service";
import {ApiService} from "../../_Service/api.service";
import {ToolsService} from "../../_Service/tools.service";
import {ActivatedRoute, Router} from "@angular/router";
import {MatDialog, MatPaginator, MatSnackBar, MatTable, MatTableDataSource} from "@angular/material";
import {HttpParams} from "@angular/common/http";
import {InstanceData, InstanceModule} from "../../_Module/InstanceModule";
import {SelectionModel} from "@angular/cdk/collections";
import {PopComponent} from "../../pop/pop.component";
import {MessageCenterService} from "../../_Service/message-center.service";
import {Subscription} from "rxjs/Subscription";
import {EnvComponent} from "../../env/env.component";
import {SendCmdToInstanceComponent} from "../send-cmd-to-instance/send-cmd-to-instance.component";
import {OvserveWSService} from "../../_Service/ovserve-ws.service";

@Component({
  selector: 'app-instance',
  templateUrl: './instance.component.html',
  styleUrls: ['./instance.component.sass']
})
export class InstanceComponent implements OnInit, OnDestroy {
  pid:string;
  displayedColumns: string[] = ['checkbox', 'name', 'compose', 'IP', 'docker', 'system', 'image', 'operate'];
  dataSource = new MatTableDataSource<InstanceModule>([]);
  selection = new SelectionModel<InstanceModule>(true, []);
  params:string;

  @ViewChild('table', {static:true}) table: MatTable<InstanceModule>;
  @ViewChild(MatPaginator, {static:true}) paginator: MatPaginator;
  private subscription: Subscription;
  constructor(private route: ActivatedRoute,
              private router: Router,
              private tools: ToolsService,
              private apiService:ApiService,
              public dialog: MatDialog,
              public snackBar: MatSnackBar,
              private _messageService: OvserveWSService,
              public mc: MessageCenterService) {
    // this.subscription = mc.msg$.subscribe((res) => {
    //   console.log(res);
    // });
    this.subscription = _messageService.rp$.subscribe((res) => {
      this.getInstanceList(res);
    })
  }

  ngOnInit() {
    this.route.parent.params.subscribe(params => {
      if(params.params){
        this.tools.parseParams(params.params, (obj) => {
          this.pid = obj['pid'];
          this.getInstanceList();
        })
      }
    });
    this.dataSource.paginator = this.paginator;
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

  loadImage(){
    if(this.selection.selected.length == 0){
      this.snackBar.open('请选择拉取镜像的实例', '',{
        duration: 1000,
        panelClass: ['error-toaster']
      })
      return false;
    }

    let request = [];
    this.selection.selected.forEach(prop => {
      let item = {}
      item['project_id'] = this.pid;
      item['instance_id'] = prop.id;
      item['cloud_type'] = 'gaea';
      request.push(item);
    });
    let formData = new HttpParams();
    formData = formData.set('ischeck', '1');
    formData = formData.set('request', JSON.stringify(request));

    this.apiService.loadImage(formData, this.pid).subscribe((res) => {
      if(res['status'] == 200){
        this.tools.StatusSuccess(res, '任务已经提交，请耐心等待');
      }else{
        this.tools.StatusError(res);
      }
      this.selection.clear();
    }, (error) => {
      this.tools.ServerError(error);
    });
  }

  runContainer(){
    if(this.selection.selected.length == 0){
      this.snackBar.open('请选择启动容器实例', '',{
        duration: 1000,
        panelClass: ['error-toaster']
      })
      return false;
    }

    let containerArr = [];
    this.selection.selected.forEach(prop => {
      if(prop.isUploadImage){
        let item = {};
        item['project_id'] = this.pid;
        item['instance_id'] = prop.id;
        item['cloud_type'] = 'gaea';
        item['compose_id'] = prop.compose_id;
        containerArr.push(item);
      }else{
        let msg = '容器:' + prop.name + '尚未拉取镜像暂时无法启动！';
        this.snackBar.open(msg, '',{
          duration: 3000,
          panelClass: ['error-toaster']
        })
      }
    });

    let formData = new HttpParams();
    formData = formData.set('pid', this.pid);
    formData = formData.set('request', JSON.stringify(containerArr));

    this.apiService.runContainer(formData, this.pid).subscribe((res) => {
      if(res['status'] == 200){
        this.tools.StatusSuccess(res, '任务已经提交请耐心等待');
      }else{
        this.tools.StatusError(res);
      }
      this.selection.clear();
    }, (error) => {
      this.tools.ServerError(error);
    })
  }

  setEnv(element){
    if(element){
      let params = encodeURI('pid=' + this.pid + '&sid=' + element.id + '&cid=' + element.compose_id)
      this.router.navigate(['../env', params], {relativeTo: this.route});
    }
  }

  delIns(){
    if(this.selection.selected.length == 0){
      this.snackBar.open('请选择删除的实例', '',{
        duration: 1000,
        panelClass: ['error-toaster']
      });

      return false;
    }

    let reusult = this.getDelInsResult();
    let dialogRef = this.dialog.open(PopComponent, {
      height: '60%',
      width: '50%',
      disableClose: true,
      autoFocus: false,
      data: {
        ids: reusult.ids,
        name: reusult.names,
        type: 'deleteServer',
        title: null,
        msg: '确认要删除如下实例吗？部分实例还有运行容器，请手动处理！',
        project_id: this.pid,
        params: reusult.params,
        dockerNum: reusult.dockerNum
      }
    });

    dialogRef.afterClosed().subscribe(result => {
      if (result == 'done') {
        this.getInstanceList();
        this.selection.clear();
      }
    });
  }

  sendCmdToIns(){
    if(this.selection.selected.length == 0){
      this.snackBar.open('请选择发送的实例', '',{
        duration: 1000,
        panelClass: ['error-toaster']
      });
      return false;
    }
    let request = this.getRequest();
    let dialogRef = this.dialog.open(SendCmdToInstanceComponent, {
      height: '60%',
      width: '50%',
      disableClose: true,
      autoFocus: false,
      data: {
        request: request,
        pid: this.pid,
        type: 100
      }
    });

    dialogRef.afterClosed().subscribe(result => {
      this.selection.clear();
    });
  }

  updateHarbor(){
    let formData = new HttpParams();
    formData = formData.set('pid', this.pid);
    this.apiService.updateHarbor(formData).subscribe((res) => {
      if(res['status'] == 200){
        this.tools.StatusSuccess(res, '任务请提交，请稍候片刻~');
      }else{
        this.tools.StatusError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
    this.selection.clear();
    return false;
  }

  stopContainer(){
    if(this.selection.selected.length == 0){
      this.snackBar.open('请选择要关闭容器的实例', '',{
        duration: 1000,
        panelClass: ['error-toaster']
      });
      return false;
    }

    let reusult = this.getDelInsResult();
    let request = this.getStopContainerResult();
    let dialogRef = this.dialog.open(PopComponent, {
      height: '60%',
      width: '50%',
      disableClose: true,
      autoFocus: false,
      data: {
        ids: reusult.ids,
        name: reusult.names,
        type: 'stopContainerForServer',
        title: null,
        msg: '确认要关闭以下实例的容器吗？',
        project_id: this.pid,
        params: request
      }
    });

    dialogRef.afterClosed().subscribe(result => {
      if (result == 'done') {
        this.getInstanceList();
        this.selection.clear();
      }
    });
  }

  private getInstanceList(type?){
    if(!type || type == 'instance'){
      let formData = new HttpParams();
      formData = formData.set('pid', this.pid);
      this.apiService.getTopologyList(formData, this.pid).subscribe((res) => {
        if(res['status'] == 200){
          let option = new InstanceData<InstanceModule>(res['data']);
          this.dataSource.data = option.option;
        }else{
          this.tools.StatusError(res);
        }
      }, (error) => {
        this.tools.ServerError(error);
      })
    }
  }

  private getRequest(){
    let result = [];

    this.selection.selected.forEach(prop => {
      let item = {};
      item['id'] = prop.id;
      item['cloud_type'] = 'gaea';
      item['ip'] = prop.internal_ip;

      result.push(item);
    });

    return result;
  }

  private getStopContainerResult(){
    let result = [];

    this.selection.selected.forEach(prop => {
      let item = {};
      item['server_id'] = prop.id;
      item['type'] = 'gaea';

      result.push(item);
    });

    return result;
  }

  private getDelInsResult(){
    let result:any = {};
    result['names'] = [];
    result['params'] = [];
    result['dockerNum'] = {};
    result['ids'] = [];

    this.selection.selected.forEach(prop => {
      let item = {}
      item['id'] = prop.id;
      item['type'] = 'gaea';
      item['ip'] = prop.internal_ip;

      result['params'].push(item);
      result['names'].push(prop.name);
      result['ids'].push(prop.id);
      result['dockerNum'][prop.id] = prop.dockerNumber;
    });

    return result;
  }
}
