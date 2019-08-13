import { Component, OnInit } from '@angular/core';
import {FormBuilder, FormGroup, Validators} from "@angular/forms";
import {ApiService} from "../_Service/api.service";
import {HttpParams} from "@angular/common/http";
import {ToolsService} from "../_Service/tools.service";
import {Router} from "@angular/router";
import {AuthServiceService} from "../_Service/auth-service.service";

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.sass']
})
export class LoginComponent implements OnInit {
  myForm:FormGroup;
  mathRandom:string;
  urlPic:string;
  constructor(private fb:FormBuilder,
              private apiService:ApiService,
              private router: Router,
              public authService: AuthServiceService,
              public tools:ToolsService) { }

  ngOnInit() {
    let token = this.authService.getToken();
    this.initForm();

    if(token && token != undefined && token != 'undefined'){
      let expirDate = this.authService.getExpriresDate(token);
      let currentTime = new Date().getTime();

      if(currentTime < (expirDate - 300000)){
        this.router.navigate(['/home']);
      }

      if (currentTime >= (expirDate - 300000) && currentTime < expirDate){
        //拿新的token
        this.apiService.getJwt().subscribe((res) => {
          localStorage.setItem('currentToken', '');
          if(res['status'] == 200){
            let token = res['data']['jwt'];
            localStorage.setItem('currentToken', token);
            this.router.navigate(['/home']);
          }else{
            this.tools.StatusError(res);
            this.getVerificationCode();
          }
        }, () => {});
      }
    }

    this.getVerificationCode();
  }

  getCaptcha(){
    this.getVerificationCode();
  }

  submitForm(){
    this.tools.checkForm(this.myForm);

    if(this.myForm.status == 'VALID'){
      let formData = new HttpParams();
      formData = formData.set('name', this.myForm.get('username').value);
      formData = formData.set('passwd', this.myForm.get('password').value);
      formData = formData.set('mobile', this.mathRandom);
      formData = formData.set('smscode', this.myForm.get('verificationCode').value);

      this.apiService.login(formData).subscribe((res) => {

        if(res['status'] == 200){
          let token = res['jwt'];
          localStorage.setItem('currentToken', token);
          this.router.navigate(['/home']);
        }else{
          this.tools.StatusError(res);
          this.getVerificationCode();
        }
      }, (error) => {
        this.tools.ServerError(error);
      })
    }
  }

  private initForm(){
    this.myForm = this.fb.group({
      username: ['', Validators.required],
      password: ['', Validators.required],
      verificationCode: ['', Validators.required]
    });

  }

  private getVerificationCode(){
    this.mathRandom = this.getMathRand();
    let formData = new HttpParams();
    formData = formData.set('type', 'login');
    formData = formData.set('mobile', this.mathRandom);

    this.apiService.verifycode(formData).subscribe((res) => {
      if(res['data']){
        this.urlPic = res['data']['url'] + '?v=' + res['data']['v']
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }

  private getMathRand(){
    let Num:string = '';
    for (var i = 1; i < 12; i++) {
      Num += (Math.floor(Math.random() * 10)).toString();
    }
    return Num;
  }
}
