import { Injectable } from '@angular/core';
import {Subject} from "rxjs/Subject";
import {HomeComponent} from "../home/home.component";
import {ConsoleComponent} from "../console/console.component";
import {WebsocketService} from "./websocket.service";

@Injectable()
export class OvserveWSService {
  private observiceWS =  new Subject<WebsocketService>();
  private observeConsole = new Subject<ConsoleComponent>();
  private observePageRefresh = new Subject<HomeComponent>();

  ws$ = this.observiceWS.asObservable();
  console$ = this.observeConsole.asObservable();
  rp$ = this.observePageRefresh.asObservable();

  public setMessage(msg){
    this.observiceWS.next(msg);
  }

  public setConsoleMessage(msg){
    this.observeConsole.next(msg)
  }

  public setRefreshPageMessage(msg){
    this.observePageRefresh.next(msg)
  }
}
