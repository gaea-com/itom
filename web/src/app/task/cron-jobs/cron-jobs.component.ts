import {Component, OnInit, ViewChild} from '@angular/core';
import {Location} from "@angular/common";
import {ActivatedRoute} from "@angular/router";
import {ToolsService} from "../../_Service/tools.service";
import {ApiService} from "../../_Service/api.service";
import {MatDialog, MatPaginator, MatSnackBar, MatTable, MatTableDataSource} from "@angular/material";
import {HttpParams} from "@angular/common/http";
import {CronJobData, CronJobModule} from "../../_Module/CronJobModule";
import {PopComponent} from "../../pop/pop.component";

@Component({
  selector: 'app-cron-jobs',
  templateUrl: './cron-jobs.component.html',
  styleUrls: ['./cron-jobs.component.sass']
})
export class CronJobsComponent implements OnInit {
  pid:string;
  dataSource = new MatTableDataSource<CronJobModule>([]);
  displayedColumns = [ 'name', 'description', 'type', 'taskName', 'operate'];
  @ViewChild('table', {static:true}) table: MatTable<Element>;
  @ViewChild(MatPaginator, {static:true}) paginator: MatPaginator;
  constructor(private tools:ToolsService,
              private apiService:ApiService,
              public snackBar: MatSnackBar,
              private location: Location,
              public dialog: MatDialog,
              private route: ActivatedRoute) { }

  ngOnInit() {
    this.route.parent.params.subscribe(params => {
      if(params.params){
        this.tools.parseParams(params.params, (obj) => {
          this.pid = obj['pid'];
        })
      }
    });

    this.getCronJob();
    this.dataSource.paginator = this.paginator;
  }


  delete(element){
    if(element){
      let dialogRef = this.dialog.open(PopComponent, {
        height: '60%',
        width: '50%',
        disableClose: true,
        autoFocus: false,
        data: {
          ids: [element.id],
          name: [element.name],
          type: 'deleteCronJob',
          title: '定时任务',
          project_id: this.pid
        }
      });

      dialogRef.afterClosed().subscribe(result => {
        if (result == 'done') {
          this.getCronJob();
        }
      });
    }
  }

  private getCronJob(){
    let formData = new HttpParams();
    formData = formData.set('pid', this.pid);
    this.apiService.getCronJob(formData, this.pid).subscribe((res) => {
      if(res['status'] == 200){
        let option = new CronJobData<CronJobModule>(res['data']);

        this.dataSource.data = option.option;
      }else{
        this.tools.StatusError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }

}
