 import {Component, Inject, OnInit} from '@angular/core';
 import {MAT_BOTTOM_SHEET_DATA, MatBottomSheetRef} from "@angular/material";
 import {HomeComponent} from "../home/home.component";
 import {MessageHashMoudle, MessageModule} from "../_Module/MessageModule";
 import {Subscription} from "rxjs/Subscription";
 import {OvserveWSService} from "../_Service/ovserve-ws.service";
 import {Observable} from "rxjs/Rx";
 import {EventConfigModule, EventData, EventItemConfig, WSConfigModule} from "../_Module/_WSConfig";
 import {MessageCenterService} from "../_Service/message-center.service";
 import {DOCUMENT} from "@angular/common";
 import {ToolsService} from "../_Service/tools.service";


 @Component({
  selector: 'app-console',
  templateUrl: './console.component.html',
  styleUrls: ['./console.component.sass']
})
export class ConsoleComponent implements OnInit {
  private subscription: Subscription;
  messageList:MessageModule[];
  messageHash:MessageHashMoudle;
  messageIDArray:string[];
  messageListStatus: Observable<number | MessageModule[]>;
  isDes:boolean = false;
  isGroup:boolean = false;
  eventConfig:any;

  constructor(private _bottomSheetRef: MatBottomSheetRef<HomeComponent>,
              @Inject(MAT_BOTTOM_SHEET_DATA) public data: any,
              private _consoleMessageService: OvserveWSService,
              private tools: ToolsService) {
    this.subscription = _consoleMessageService.console$.subscribe(res => {
      if(res){
        this.setMsgList(res, this.messageList);
      }
    });

    this.messageListStatus = Observable.interval(1000).map(i => this._setMessage(i));
  }

  ngOnInit() {
    this.eventConfig = new EventData<EventItemConfig>(WSConfigModule);
    this.messageList = this.data;
  }

  sort(type){
    this._clearLine();

    if(type == 'des'){
      if(this.isDes){
        this.messageList.reverse();
      }
      this.isDes = false;
    }

    if(type == 'asc'){
      if(!this.isDes){
        this.messageList.reverse();
      }
      this.isDes = true;
    }
  }

  group(type){
    if(type == 'group'){
      this.messageHash = this.getMessageHashList();
      this.messageIDArray = this.getMessageIDArray();
      this.isGroup = true;
    }

    if(type == 'time'){
      this.isGroup = false;
    }
  }

  delete(){
    this.messageList = [];
    // this.messageList[this.messageList.length] = {'line': true};
  }

  close(event){
    this._clearLine();
    this._bottomSheetRef.dismiss({data: this.messageList});
    event.preventDefault();
  }

  checkLog(element){
      this.tools.checkLog(element);
  }

  private getMessageHashList(){
    let result:MessageHashMoudle = new MessageHashMoudle();
    this.messageList.forEach(prop => {
      if(result[prop.ID]){
        result[prop.ID] = this.setMsgList(prop, result[prop.ID]);
      }else{
        result[prop.ID] = [];
        result[prop.ID] = this.setMsgList(prop, result[prop.ID]);
      }
    });
    return result
  }

  private getMessageIDArray(){
    let result = [];
    Object.keys(this.messageHash).forEach(ID => {
      result.push(ID);
    });
    return result;
  }

  private setMsgList(msgItem, messageList){
    messageList[messageList.length] = msgItem;
    return messageList;
  }

  private _setMessage(i){
    return this.messageList
  }

  private _clearLine(){
    this.messageList.forEach((item, i) => {
      if(item.line){
        this.messageList.splice(i, 1);
      }
    });
  }
}
