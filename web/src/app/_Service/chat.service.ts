import { Injectable } from '@angular/core';
import {Subject} from "rxjs/Subject";
import {WebsocketService} from "./websocket.service";
import {AuthServiceService} from "./auth-service.service";

@Injectable()
export class ChatService {
  public message: Subject<any>;
  constructor(private wsService: WebsocketService,
              private authService: AuthServiceService) {
    let uid = this.authService.getUid();

    if(uid){
      this.message = <Subject<any>>wsService
        .connect(uid)
    }

  }

  sendInit(){
    let uid = this.authService.getUid();
    this.message.next({event:'login', uid:uid});
  }
}
