import {Component, OnInit, ViewChild} from '@angular/core';
import {MatDialog, MatPaginator, MatSnackBar, MatTable, MatTableDataSource} from "@angular/material";
import {ApiService} from "../../_Service/api.service";
import {ActivatedRoute} from "@angular/router";
import {ToolsService} from "../../_Service/tools.service";
import {ContainerModule} from "../../_Module/ContainerModule";
import {UserData, UserModule} from "../../_Module/UserModule";
import {SelectionModel} from "@angular/cdk/collections";
import {PopUploadToDockerComponent} from "../../container/pop-upload-to-docker/pop-upload-to-docker.component";
import {PopCreateAccountComponent} from "../pop-create-account/pop-create-account.component";
import {HttpParams} from "@angular/common/http";

@Component({
  selector: 'app-account-list',
  templateUrl: './account-list.component.html',
  styleUrls: ['./account-list.component.sass']
})
export class AccountListComponent implements OnInit {
  displayedColumns: string[] = ['name', 'type', 'status', 'loginTime', 'registerTime', 'operate'];
  dataSource = new MatTableDataSource<UserModule>([]);
  selection = new SelectionModel<UserModule>(true, []);
  @ViewChild('table', {static:true}) table: MatTable<UserModule>;
  @ViewChild(MatPaginator, {static:true}) paginator: MatPaginator;
  constructor(private route: ActivatedRoute,
              public tools:ToolsService,
              public snackBar: MatSnackBar,
              public dialog: MatDialog,
              private apiService:ApiService) { }

  ngOnInit() {
    this.dataSource.paginator = this.paginator;
    this.getAccountList();
  }
  password:string = '';

  createAccount(element?){
    let dialogRef = this.dialog.open(PopCreateAccountComponent, {
      height: '60%',
      width: '50%',
      disableClose: true,
      autoFocus: false,
      data: {
        popType: 'userForm',
        id: (element) ? element.id : '',
        name: (element) ? element.name : '',
        status: (element) ? element.statusCode : '',
        type: (element) ? element.typeCode : ''
      }
    });

    dialogRef.afterClosed().subscribe(result => {
      this.getAccountList();
    });
  }

  resetPassword(element){
    let formData = new HttpParams();
    formData = formData.set('uid', element.id);
    this.apiService.resetPassword(formData).subscribe((res) => {
      if(res['status'] == 200){
        let dialogRef = this.dialog.open(PopCreateAccountComponent, {
          height: '60%',
          width: '50%',
          disableClose: true,
          autoFocus: false,
          data: {
            popType: 'showPassword',
            password: res['code']
          }
        });
        // this.snackBar.open('密码重置成功:' + res['code'], '', {
        //   duration: 5000,
        //   panelClass: ['success-toaster']
        // })
      }else{
        this.tools.StatusError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }

  delete(element){
    this.apiService.deleteUser(element.id).subscribe((res) => {
      if(res['status'] == 200){
        this.tools.StatusSuccess(res, '删除成功')
        this.getAccountList()
      }else{
        this.tools.ServerError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }

  private getAccountList(){
    this.apiService.getUser().subscribe((res) => {
      if(res['status'] == 200){
        let option = new UserData<UserModule>(res['data']);
        this.dataSource.data = option.option;
      }else{
        this.tools.StatusError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }
}
