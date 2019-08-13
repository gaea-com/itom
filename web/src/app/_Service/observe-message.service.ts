import { Injectable } from '@angular/core';
import {Observable} from "rxjs/Observable";
import {debounceTime, map} from "rxjs/operators";
import {MessageModule} from "../_Module/MessageModule";
import {interval} from "rxjs/internal/observable/interval";

@Injectable()
export class ObserveMessageService {
  timmer:any = null
  constructor() { }

  public getMessageData(msg):Observable<MessageModule[]>{
    let data = new Observable<MessageModule[]|any>(observer => {
      setInterval(()=> {
        observer.next(msg)
      }, 1000);
    });
    return data
  }
}
