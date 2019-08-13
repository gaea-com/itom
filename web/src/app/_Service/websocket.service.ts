import {Inject, Injectable} from '@angular/core';
import * as Rx from 'rxjs/Rx';
import {webSocket} from "rxjs/webSocket";
import {OvserveWSService} from "./ovserve-ws.service";
import {DOCUMENT} from "@angular/common";

@Injectable()
export class WebsocketService {
  private socket;
  public url:string;
  private instanceListService: OvserveWSService;
  constructor(private _instanceListService: OvserveWSService,
              @Inject(DOCUMENT) private document: any) {
    this.instanceListService = _instanceListService;
    this.url = 'ws://' + this.document.location.hostname;
  }

  public connect(uid): Rx.Subject<MessageEvent>{
    this.socket = webSocket(this.url + ':9501');

    this.socket.subscribe(msg => {
      if(msg.event){
        this.instanceListService.setMessage(msg);
      }
    })
    return this.socket
  }
}
