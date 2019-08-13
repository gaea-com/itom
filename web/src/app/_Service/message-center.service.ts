import { Injectable } from '@angular/core';
import {HomeComponent} from "../home/home.component";
import {Subject} from "rxjs/Subject";

@Injectable()
export class MessageCenterService {
  private center =  new Subject<HomeComponent>();
  msg$ = this.center.asObservable();

  public setMsg(){
    this.center.next()
  }
}
