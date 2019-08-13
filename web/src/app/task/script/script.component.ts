import {Component, OnInit, ViewChild} from '@angular/core';
import {ActivatedRoute, Router} from "@angular/router";
import {ToolsService} from "../../_Service/tools.service";
import {ApiService} from "../../_Service/api.service";
import {ScriptData, ScriptModule} from "../../_Module/ScriptModule";
import {animate, state, style, transition, trigger} from "@angular/animations";
import {MatDialog, MatPaginator, MatSnackBar, MatTable, MatTableDataSource} from "@angular/material";
import {AuthServiceService} from "../../_Service/auth-service.service";
import {HttpParams} from "@angular/common/http";
import {PopComponent} from "../../pop/pop.component";
import {PopRunScriptComponent} from "../pop-run-script/pop-run-script.component";

@Component({
  selector: 'app-script',
  templateUrl: './script.component.html',
  styleUrls: ['./script.component.sass'],
  animations: [
    trigger('detailExpand', [
      state('collapsed', style({height: '0px', minHeight: '0'})),
      state('expanded', style({height: '*'})),
      transition('expanded <=> collapsed', animate('225ms cubic-bezier(0.4, 0.0, 0.2, 1)')),
    ]),
  ]
})
export class ScriptComponent implements OnInit {
  pid:string;
  dataSource = new MatTableDataSource<ScriptModule|any>([]);
  columnsToDisplay = ['name', 'type', 'description', 'userName', 'operate'];
  expandedElement: ScriptModule | null;
  uid:number;
  @ViewChild('table', {static:true}) table: MatTable<Element>;
  @ViewChild(MatPaginator, {static:true}) paginator: MatPaginator;
  constructor(private tools:ToolsService,
              private apiService:ApiService,
              private route: ActivatedRoute,
              private router: Router,
              public snackBar: MatSnackBar,
              public dialog: MatDialog,
              private authService: AuthServiceService) { }

  ngOnInit() {
    this.uid = this.authService.getUid();
    this.route.parent.params.subscribe(params => {
      if(params.params){
        this.tools.parseParams(params.params, (obj) => {
          this.pid = obj['pid'];
          this.getScriptList();
        })
      }
    });
    this.dataSource.paginator = this.paginator;
  }

  run(element){
    if(element && element.canRun){
      let dialogRef = this.dialog.open(PopRunScriptComponent, {
        height: '60%',
        width: '50%',
        disableClose: true,
        autoFocus: false,
        data: {
          "type": parseInt(element.typeCode),
          "id": element.id,
          "name": element.name,
          "script": element.script,
          "pid": this.pid
        }
      });

      dialogRef.afterClosed().subscribe(result => {
        if (result && result == 100) {
          this.router.navigate(['../createCustomerGroup', encodeURI('type=100')], {relativeTo: this.route});
        }

        if (result && result == 200) {
          ['../projectEdit', element.id]
          this.router.navigate(['../createCustomerGroup', encodeURI('type=200')], {relativeTo: this.route});
        }
      });
    }else{
      this.snackBar.open('您暂时无法运行这条命令', '',{
        duration: 1000,
        panelClass: ['error-toaster']
      });
    }
  }

  delete(element){
    if(element && this.uid == element.userId){
      let dialogRef = this.dialog.open(PopComponent, {
        height: '60%',
        width: '50%',
        disableClose: true,
        autoFocus: false,
        data: {
          ids: [element.id],
          name: [element.name],
          type: 'deleteScript',
          title: '命令',
          project_id: this.pid
        }
      });

      dialogRef.afterClosed().subscribe(result => {
        if (result == 'done') {
          this.getScriptList();
        }
      });
    }else{
      this.snackBar.open('您暂时无法删除这条命令', '',{
        duration: 1000,
        panelClass: ['error-toaster']
      });
    }
  }

  private getScriptList(){
    let formData = new HttpParams();
    formData = formData.set('pid', this.pid);
    this.apiService.getScript(formData, this.pid).subscribe((res) => {
      if(res['status'] == 200){
        let option = new ScriptData<ScriptModule>(res['data']);
        this.dataSource.data = option.option;
      }else{
        this.tools.StatusError(res)
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }
}
