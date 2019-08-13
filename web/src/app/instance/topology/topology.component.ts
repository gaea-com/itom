import {Component, OnDestroy, OnInit, ViewChild} from '@angular/core';
import {
  MatAccordion,
  MatDialog,
  MatMenuTrigger,
  MatPaginator,
  MatSnackBar,
  MatTable,
  MatTableDataSource
} from "@angular/material";
import {PopComponent} from "../../pop/pop.component";
import {AddGroupComponent} from "../add-group/add-group.component";
import {ActivatedRoute} from "@angular/router";
import {ToolsService} from "../../_Service/tools.service";
import {ApiService} from "../../_Service/api.service";
import {ChatService} from "../../_Service/chat.service";
import {ImportComponent} from "../import/import.component";
import {AddComposeComponent} from "../../compose/add-compose/add-compose.component";
import {GroupData, GroupModule} from "../../_Module/GroupModule";
import {HttpParams} from "@angular/common/http";
import {TopologyData} from "../../_Module/TopologyModule";
import {LinkInstanceComponent} from "../link-instance/link-instance.component";
import {animate, state, style, transition, trigger} from "@angular/animations";
import {FormBuilder, Validators} from "@angular/forms";
import {OvserveFileService} from "../../_Service/ovserve-file.service";
import {MessageCenterService} from "../../_Service/message-center.service";
import {Subscription} from "rxjs/Subscription";
import {OvserveWSService} from "../../_Service/ovserve-ws.service";

@Component({
  selector: 'app-topology',
  templateUrl: './topology.component.html',
  styleUrls: ['./topology.component.sass'],
  animations: [
    trigger('detailExpand', [
      state('collapsed', style({height: '0px', minHeight: '0', display: 'none'})),
      state('expanded', style({height: '*'})),
      transition('expanded <=> collapsed', animate('225ms cubic-bezier(0.4, 0.0, 0.2, 1)')),
    ]),
  ]
})
export class TopologyComponent implements OnInit, OnDestroy {
  pid:string;
  groupList:any;
  groupIds:string[];
  instanceList:any;
  private instanceListService : OvserveFileService;
  iconStatus:boolean = true;
  isShowTable:boolean = false;

  @ViewChild('trigger', {static:true}) menu:MatMenuTrigger;
  @ViewChild('table', {static:true}) table: MatTable<GroupModule|any>;
  @ViewChild(MatPaginator, {static:true}) paginator: MatPaginator;
  @ViewChild('myaccordion', {static:true}) myPanels: MatAccordion;
  private subscription: Subscription;
  private ms: OvserveWSService;
  constructor(private route: ActivatedRoute,
              private tools: ToolsService,
              private apiService:ApiService,
              public dialog: MatDialog,
              public snackBar: MatSnackBar,
              private fb:FormBuilder,
              private _messageService: OvserveWSService,
              private _instanceListService: OvserveFileService,
              public mc: MessageCenterService) {
    this.instanceListService = _instanceListService;
    this.subscription = this._messageService.rp$.subscribe((res) => {

      this.getGroupList(res);
    })

  }

  ngOnInit() {
    this.route.parent.params.subscribe(params => {
      if(params.params){
        this.tools.parseParams(params.params, (obj) => {
          this.pid = obj['pid'];
          this.getGroupList();
        })
      }
    });
  }

  ngOnDestroy() {
    this.subscription.unsubscribe();
  }

  linkGroup(groupId){
    if(!groupId){
      this.snackBar.open('无法关联虚拟组', '',{
        duration: 1000,
        panelClass: ['error-toaster']
      });

      return false;
    }

    let currentGroupItem = this.instanceList[0] || [];

    if(currentGroupItem.length == 0){
      this.snackBar.open('已无需要关联的实例', '',{
        duration: 1000,
        panelClass: ['error-toaster']
      });

      return false
    }

    let dialogRef = this.dialog.open(LinkInstanceComponent, {
      height: '40%',
      width: '50%',
      disableClose: true,
      autoFocus: false,
      data: {
        project_id: this.pid,
        groupId: groupId,
        unbindInstanceList: currentGroupItem
      }
    });

    dialogRef.afterClosed().subscribe(result => {
      if(result['status'] == 'done'){
        // 减少虚拟组
        let idx = this.tools.getIndex(this.instanceList['0'], result['instance']);

        this.instanceList['0'].splice(idx, 1);

        if(this.instanceList['0'].length == 0){
          this.getGroupList();
        }else{
          this.getGroupList();
        }
      }

      return false;
    });
    return false;
  }

  toggleExpanded(id){
    this.groupList[id]['iconStatus'] = !this.groupList[id]['iconStatus']
    return false;
  }

  toggleGroup(event){
    let status = event.value;
    Object.keys(this.groupList).forEach(prop => {
      this.groupList[prop]['iconStatus'] = (status == 'false') ? false : true;
    });
    return false;
  }

  getValue(event){
    let E = event.event;
    let status = event.status;
    if(E == 'addGroup' && status == 'done'){
      this.getGroupList();
      this.menu.closeMenu();
    }

    if(E == 'addCompose' && status == 'done'){
      this.menu.closeMenu();
    }

    if(E == 'importInstance' && status == 'done'){
      this.menu.closeMenu();
    }
  }

  delGroup(groupId){
    if(!parseInt(groupId)){
      this.snackBar.open('无法删除虚拟组', '',{
        duration: 1000,
        panelClass: ['error-toaster']
      });

      return false;
    }

    let currentGroupItem = this.instanceList[groupId] || [];

    if(currentGroupItem.length > 0){
      this.snackBar.open('该实例组下尚有实例无法删除！', '',{
        duration: 1000,
        panelClass: ['error-toaster']
      });

      return false;
    }

    let dialogRef = this.dialog.open(PopComponent, {
      height: '60%',
      width: '50%',
      disableClose: true,
      autoFocus: false,
      data: {
        ids: [groupId],
        name: [this.groupList[groupId]['name']],
        type: 'group',
        title: '实例组',
        project_id: this.pid
      }
    });

    dialogRef.afterClosed().subscribe(result => {
      if(result == 'done'){
        this.getGroupList();
      }
    });
  }

  importInstance(){
    let dialogRef = this.dialog.open(ImportComponent, {
      height: '40%',
      width: '50%',
      disableClose: true,
      autoFocus: false,
      data: {
        project_id: this.pid
      }
    })
  }

  private getGroupList(type?) {
    if(!type || type == 'topology'){
      this.groupIds = [];
      //获取groupId;
      this.apiService.getGroupList(this.pid).subscribe((res) => {
        if (res['status'] == 200) {
          let option = new GroupData<GroupModule>(res['data']);
          this.groupList = option.option;
          this.getCloudList();
        } else {
          this.tools.StatusError(res);
        }
      }, (error) => {
        this.tools.ServerError(error);
      });
    }
  }

  private getCloudList(){
    let formData = new HttpParams();
    formData = formData.set('gid', '0');
    formData = formData.set('pid', this.pid);
    this.apiService.getTopologyList(formData, this.pid).subscribe((res) => {
      if(res['status'] == 200){
        let option = new TopologyData<any>(res['data']);
        this.instanceList = option.option;
        Object.keys(this.instanceList).forEach(Id => {
          if(Id == '0'){
            let item:any = {};
            item['name'] = '虚拟组';
            item['id'] = 0;
            item['iconStatus'] = true;
            item['group'] = [];
            this.groupList['0'] = new GroupModule(item);
          }
        });

        Object.keys(this.groupList).forEach(ID => {
          this.groupIds.push(ID);
        });
        this.groupIds.reverse();

        this.isShowTable = true;
        this.instanceListService.setInstanceList(this.instanceList);

      }else{
        this.tools.StatusError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }
}
const dictColumn = {
  "name": "实例名称",
  "description": "描述",
  "compose": "编排模版",
  "ip": "IP",
  'operate': ""
};
