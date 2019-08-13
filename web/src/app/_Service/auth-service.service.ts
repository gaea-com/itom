import {Inject, Injectable} from '@angular/core';
import * as jwt_decode from "jwt-decode";

@Injectable()
export class AuthServiceService {
  public token:any;
  public cookie:any;
  constructor() {}

  public getToken(){
    this.token = (localStorage.getItem('currentToken')) ? localStorage.getItem('currentToken') : '';
    return this.token;
  }

  public getExpriresDate(token){
    let payload = jwt_decode(token);
    return payload.exp * 1000;
  }

  public getUid(){
    this.token = this.getToken();
    let payload = (this.token) ? jwt_decode(this.token) : null;
    return (payload) ? payload.uid : null;
  }

  public getRole(){
    this.token = this.getToken();
    let payload = (this.token) ? jwt_decode(this.token) : null;
    return (payload) ? payload.rol : null;
  }
}
