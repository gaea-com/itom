import {Component, OnDestroy, OnInit} from '@angular/core';
import {AuthServiceService} from "../_Service/auth-service.service";
import {ChatService} from "../_Service/chat.service";
import {MatBottomSheet, MatBottomSheetConfig, MatDialog} from "@angular/material";
import {ConsoleComponent} from "../console/console.component";
import {OvserveWSService} from "../_Service/ovserve-ws.service";
import {Subscription} from "rxjs/Subscription";
import {MessageModule} from "../_Module/MessageModule";
import {MessageCenterService} from "../_Service/message-center.service";
import {ToolsService} from "../_Service/tools.service";
import {EventData, EventItemConfig, WSConfigModule} from "../_Module/_WSConfig";
import {LinkInstanceComponent} from "../instance/link-instance/link-instance.component";
import {ResetPasswordComponent} from "../manage/reset-password/reset-password.component";
import {PopComponent} from "../pop/pop.component";
import {Router} from "@angular/router";
import {ApiService} from "../_Service/api.service";

@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.sass']
})
export class HomeComponent implements OnInit, OnDestroy {
  private subscription: Subscription;
  msgNum:number;
  msgListData: MessageModule[] = []; //全局共用一套关系模型
  msgData:MessageModule[] = [];  //推送消息临时存储
  bottomSheetStatus:boolean;
  eventConfig:any;
  private consoleMessageService: OvserveWSService;
  constructor(private authService:AuthServiceService,
              private chatService:ChatService,
              private _bottomSheet: MatBottomSheet,
              private _messageService: OvserveWSService,
              private apiService: ApiService,
              private tools: ToolsService,
              private router: Router,
              public dialog: MatDialog) {
    this.consoleMessageService = _messageService;
    this.subscription = _messageService.ws$.subscribe((res) =>{
      if(res){
        this.setMessage(res);
      }
    })
  }

  ngOnInit() {
    this.eventConfig = new EventData<EventItemConfig>(WSConfigModule);
    this.chatService.sendInit();
  }

  ngOnDestroy(){
    this.subscription.unsubscribe();
  }

  modifyPassword(){
    let dialogRef = this.dialog.open(ResetPasswordComponent, {
      height: '40%',
      width: '50%',
      disableClose: true,
      autoFocus: false,
      data: {}
    });
  }

  logout(){
    let dialogRef = this.dialog.open(PopComponent, {
      height: '40%',
      width: '50%',
      disableClose: true,
      autoFocus: false,
      data: {
        header: "退出确认",
        msg: '确认退出登录么？'
      }
    });

    dialogRef.afterClosed().subscribe(result => {
      if(result){
        this.apiService.logout().subscribe(() => {
          localStorage.setItem('currentToken', '');
          //注销WS
          //关掉消息弹窗
          this._bottomSheet.dismiss();
          //清空消息内容
          this.msgData = [];
          this.router.navigate(['/login']);
        }, (error) => {
          this.tools.ServerError(error);
        })
      }
    })
  }

  getBottom(){
    if(this.bottomSheetStatus){
      return false
    }

    this.bottomSheetStatus = true;
    let config = this.getConfig();

    let bottomSheet = this._bottomSheet.open(ConsoleComponent, config);
    this.msgData = [];
    this.msgNum = null;

    bottomSheet.afterDismissed().subscribe( result => {
      if(result && result.data){
        this.msgListData = result.data
      }

      this.bottomSheetStatus = false;
    });
  }

  private getConfig(){
    let config: MatBottomSheetConfig;
    let msg = this.getMsgListData();
    config = {
      hasBackdrop: false,
      disableClose: true,
      direction: 'ltr',
      data: msg
    };
    return config
  }

  private setMessage(res){
    if(res.event){
      let url = this.eventConfig.getEventMethod(res.event);
      let isEnd = this.eventConfig.isEnd(res);
      // console.log('url' + url);
      // console.log('isEnd: ' + isEnd);
      //event 分task container instance topology
      //如果task 则显示 查看详情button 其余都走event分之

      if(url != 'task' && isEnd){
        //推送消息
        this.consoleMessageService.setRefreshPageMessage(url);
      }

      if(this.bottomSheetStatus){
        let messageItem = new MessageModule(res, this.eventConfig, url);
        this.consoleMessageService.setConsoleMessage(messageItem);
      }else{
        this.msgData[this.msgData.length] = new MessageModule(res, this.eventConfig, url);
        this.msgNum = this.msgData.length;
      }

    }
  }

  private getMsgListData(msgItem?){
    let result = [];

    if(msgItem){
      this.msgListData[this.msgListData.length] = msgItem;
      result = this.msgListData;
    }else{
      result = this.msgListData.concat(this.msgData);
    }

    return result
  }
}
