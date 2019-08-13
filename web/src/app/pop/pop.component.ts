import {Component, Inject, OnInit} from '@angular/core';
import {ApiService} from "../_Service/api.service";
import {MAT_DIALOG_DATA, MatDialogRef, MatSnackBar} from "@angular/material";
import {ProjectListComponent} from "../project/project-list/project-list.component";
import {HttpParams} from "@angular/common/http";
import {ProjectData, ProjectModule} from "../_Module/ProjectModule";
import {ToolsService} from "../_Service/tools.service";

@Component({
  selector: 'app-pop',
  templateUrl: './pop.component.html',
  styleUrls: ['./pop.component.sass']
})
export class PopComponent implements OnInit {
  name:string[];
  type:string;
  title:string;
  ids:number[];
  header:string;
  constructor(
    private apiServer: ApiService,
    public tools:ToolsService,
    public dialogRef: MatDialogRef<ProjectListComponent>,
    @Inject(MAT_DIALOG_DATA) public data: any,
    public snackBar: MatSnackBar
  ) { }

  ngOnInit() {
    this.name = this.data.name;
    this.type = this.data.type;
    this.ids = this.data.ids;
    this.title = (this.data.title) ? '确认要删除如下'+this.data.title+'吗？' : this.data.msg;
    this.header = (this.data.header) ? this.data.header : "删除确认";
  }

  confirm() {
    let formData = new HttpParams();
    // formData = formData.set('name', this.myForm.get('name').value);
    // formData = formData.set('description', this.myForm.get('description').value);
    switch (this.type){
      case 'project':
        let id = this.data.project_id;
        this.apiServer.delProject(id).subscribe((res) => {
          if(res['status'] == 200){
            this.tools.StatusSuccess(res, '删除成功');
            this.dialogRef.close('done');
          }else{
            this.tools.StatusError(res);
          }
        }, (error) => {
          this.tools.ServerError(error);
        });
        break;
      case 'group':
        let groupId = this.data.ids[0];
        this.apiServer.deleteGroup(groupId, this.data.project_id).subscribe((res) => {
          if(res['status'] == 200){
            this.tools.StatusSuccess(res, '删除成功');
            this.dialogRef.close('done');
          }else{
            this.tools.StatusError(res);
          }
        }, (error) => {
          this.tools.ServerError(error);
        });
        break;
      case 'deleteServer':
        formData = formData.set('id', JSON.stringify(this.data.params));
        this.apiServer.deleteServer(formData, this.data.project_id).subscribe((res) => {
          if(res['status'] == 200){
            this.tools.StatusSuccess(res, '删除成功');
            this.dialogRef.close('done');
          }else{
            let errStr = '0';
            if(res['errID'].length > 0){
              errStr = res['errorMsg'].map((res) => {
                return res
              }).join(',');
            }

            let susStr = '0';
            if(res['susscessID'].length > 0){
              susStr = res['susscessID'].map((res) => {
                return res['name']
              }).join(',');
            }

            let msg = '失败：'+ errStr +' ; 成功:' + susStr;
            this.snackBar.open(msg, '',{
              duration: 5000,
              panelClass: ['error-toaster']
            });
            this.dialogRef.close('done');
          }
        },(error) => {
          this.tools.ServerError(error);
        });
          break;
      case 'stopContainer':
        formData = formData.set('id', JSON.stringify(this.data.ids));
        this.apiServer.stopContainer(formData, this.data.project_id).subscribe((res) => {
          if(res['status'] == 200){
            this.tools.StatusSuccess(res, '删除成功');
            this.dialogRef.close('done');
          }else{
            this.tools.StatusError(res);
            this.dialogRef.close()
          }
        }, (error) => {
          this.tools.ServerError(error);
        })
        break;
      case 'deleteScript':
        formData = formData.set('id', this.data.ids[0]);
        this.apiServer.deleteScript(formData, this.data.project_id).subscribe((res) => {
          if(res['status'] == 200){
            this.tools.StatusSuccess(res, '删除成功');
            this.dialogRef.close('done');
          }else{
            this.dialogRef.close('done');
          }
        }, (error) => {
          this.tools.ServerError(error);
        });
        break;
      case 'deleteCustomerGroup':
        formData = formData.set('id', this.data.ids[0]);
        this.apiServer.deleteCustomerGroup(formData, this.data.project_id).subscribe((res) => {
          if(res['status'] == 200){
            this.tools.StatusSuccess(res, '删除成功');
            this.dialogRef.close('done');
          }else{
            this.dialogRef.close('done');
          }
        }, (error) => {
          this.tools.ServerError(error);
        });
        break;
      case 'deleteTask':
        formData = formData.set('id', this.data.ids[0]);
        this.apiServer.deleteTask(formData, this.data.project_id).subscribe((res) => {
          if(res['status'] == 200){
            this.tools.StatusSuccess(res, '删除成功');
            this.dialogRef.close('done');
          }else{
            this.dialogRef.close('done');
          }
        }, (error) => {
          this.tools.ServerError(error);
        });
        break;
      case 'deleteCronJob':
        formData = formData.set('id', this.data.ids[0]);
        this.apiServer.deleteCronJob(formData, this.data.project_id).subscribe((res) => {
          if(res['status'] == 200){
            this.tools.StatusSuccess(res, '删除成功');
            this.dialogRef.close('done');
          }else{
            this.dialogRef.close('done');
          }
        }, (error) => {
          this.tools.ServerError(error);
        });
        break;
      case 'stopContainerForServer':
        formData = formData.set('pid', this.data.project_id);
        formData = formData.set('request', JSON.stringify(this.data.params));
        this.apiServer.stopContainerForServer(formData, this.data.project_id).subscribe((res) => {
          if(res['status'] == 200){
            this.tools.StatusSuccess(res, '删除成功');
            this.dialogRef.close('done');
          }else{
            this.dialogRef.close('done');
          }
        }, (error) => {
          this.tools.ServerError(error);
        });
        break;
      case 'Hub':
        this.apiServer.deleteHarbor({
          "name": this.data.image,
          "tag": this.data.ids[0]
        }).subscribe((res) => {
          if(res['status'] == 200){
            this.tools.StatusSuccess(res, '删除成功');
            this.dialogRef.close('done');
          }else{
            this.tools.StatusError(res);
            this.dialogRef.close('done');
          }
        }, (error) => {
          this.tools.ServerError(error);
        })
        break;
      default:
        this.dialogRef.close('done');
        break;
    }
  }
}
