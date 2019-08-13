import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {ApiService} from "../../_Service/api.service";
import {ToolsService} from "../../_Service/tools.service";
import {HttpParams} from "@angular/common/http";
import {HubData, HubModule, HubTagData, HubTagModule} from "../../_Module/HubModule";
import {MatDialog} from "@angular/material";
import {PopComponent} from "../../pop/pop.component";

@Component({
  selector: 'app-setting-detail',
  templateUrl: './setting-detail.component.html',
  styleUrls: ['./setting-detail.component.sass']
})
export class SettingDetailComponent implements OnInit {
  @Input('image') image:string;
  tagList:HubTagModule[];
  constructor(private apiService:ApiService,
              private tools:ToolsService,
              public dialog: MatDialog) { }

  ngOnInit() {
    this.getTagList();
  }

  delete(element){
    let dialogRef = this.dialog.open(PopComponent, {
      height: '60%',
      width: '50%',
      disableClose: true,
      autoFocus: false,
      data: {
        ids: [element.version],
        name: [element.tag],
        type: 'Hub',
        title: '镜像',
        image: this.image
      }
    });

    dialogRef.afterClosed().subscribe(result => {
      if(result == 'done'){
        this.getTagList();
      }
    });
  }

  private getTagList(){
    let formData = new HttpParams();
    formData = formData.set('name', this.image);
    this.apiService.getTagList(formData).subscribe((res) => {
      if(res['status'] == 200){

        let tags = res['data']['tags'] || [];
        let option = new HubTagData<HubTagModule>(tags, this.image);

        this.tagList = option.option;
      }else{
        this.tools.ServerError(res);
      }
    }, (error) => {
      this.tools.ServerError(error);
    })
  }

}
