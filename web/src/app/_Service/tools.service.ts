import {Inject, Injectable} from '@angular/core';
import {FormGroup} from "@angular/forms";
import {MatSnackBar} from "@angular/material";
import {MessageCenterService} from "./message-center.service";
import {ApiService} from "./api.service";
import {HttpParams} from "@angular/common/http";
import {Router} from "@angular/router";
import {DOCUMENT} from "@angular/common";
import {IndexBreadCrumb} from "../_Module/BreadCrumbModule";

@Injectable()
export class ToolsService {
  constructor(public snackBar: MatSnackBar,
              private apiService: ApiService,
              private router: Router,
              @Inject(DOCUMENT) private document: any,
              private mc: MessageCenterService) {
  }

  public checkForm(form:FormGroup){
    Object.keys(form.controls).forEach((field) => {
      let ctl = form.get(field);
      ctl.markAsTouched();
      ctl.updateValueAndValidity();
    })
  }

  public ServerError(result){
    if(result['status'] == 401 || result['status'] == 403){
      this.snackBar.open('暂无权限', '',{
        duration: 3000,
        panelClass: ['error-toaster']
      });
    }else{
      this.snackBar.open(result.error || result.errorMsg, '',{
        duration: 3000,
        panelClass: ['error-toaster']
      });
    }
  }

  public StatusError(result){
    this.snackBar.open(result.errorMsg || result.error, '',{
      duration: 3000,
      panelClass: ['error-toaster']
    });
  }

  public StatusSuccess(result, msg){
    this.snackBar.open(msg, '',{
      duration: 3000,
      panelClass: ['success-toaster']
    });
  }

  public parseParams(params, callback){
    let params_arr = params.split('&');
    let obj = {};
    params_arr.forEach(item => {
      let Arr = decodeURI(item).split('=');
      obj[Arr[0]] = Arr[1];
    });

    if(callback){
      callback(obj);
    }
  }

  public getIndex(arr, id, name?){
    let label = (name) ? name : 'id';
    let idx:number = 0;
    if(arr.length > 0){
      for(let i = 0; i < arr.length; i++){
        if(arr[i][label] == id){
          idx = i;
          break;
        }
      }
    }
    return idx;
  }

  public getCurrentItem(arr, id, name?){
    let label = (name) ? name : 'id';
    let element:any = null;
    if(arr.length > 0){
      for(let i = 0; i < arr.length; i++){
        if(arr[i][label] == id){
          element = arr[i];
          break;
        }
      }
    }
    return element;
  }

  public checkLog(element){
    let formData = new HttpParams();
    formData = formData.set('task_id', element.taskId);
    this.apiService.getLog(formData).subscribe((res) => {
      if(res['status'] == 200){
        let pid = res['data'][0]['project_id'];
        let projectName = res['data'][0]['project_name'];
        let params = encodeURI('pid=' + pid + '&name=' + projectName);
        this.router.navigate(['/home/detail/'+params+'/logDetail/'+element.taskId]);
      }else{
        this.StatusError(res);
      }
    }, (error) => {
      this.ServerError(error);
    })
  }

  public getEndPath(Arr){
    let dict = IndexBreadCrumb;
    let endPath = '';

    for(let i = Arr.length-1; i <= 1; i--){
      if(dict[Arr[i]] && dict[Arr[i]].path.length == 0){
        endPath = dict[Arr[i]];
        break;
      }
    }
    return endPath
  }
}
