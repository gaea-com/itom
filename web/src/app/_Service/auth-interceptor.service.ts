import { Injectable } from '@angular/core';
import {ApiService} from "./api.service";
import {AuthServiceService} from "./auth-service.service";
import {Observable} from "rxjs/internal/Observable";
import {tap} from "rxjs/operators";
import {HttpErrorResponse, HttpEvent, HttpHandler, HttpRequest, HttpResponse} from "@angular/common/http";
import {Router} from "@angular/router";

@Injectable()
export class AuthInterceptorService {

  constructor(public authService: AuthServiceService,
              public apiService: ApiService,
              private router: Router,) {
  }

  intercept(req: HttpRequest<any>, next: HttpHandler):
    Observable<HttpEvent<any>>{
    req = req.clone({
      setHeaders: {
        Authorization: `Bearer ${this.authService.token}`
      }
    });

    return next.handle(req).pipe(
      tap((event: HttpEvent<any>) => {
        if(event instanceof HttpResponse){}
      }, (err: any) => {
        if(err instanceof HttpErrorResponse){
          if(err.status == 401){
            console.log('401 了');
            localStorage.setItem('currentToken', '');
            this.router.navigate(['/login']);
          }

          if(err.status == 403){

            // let url = "https://login.gaeamobile-inc.net/passport/login?returnUrl=https://gsl-new-test.gaeamobile-inc.net";
            // location.href = url;
          }
        }
      })
    );
  }
}
