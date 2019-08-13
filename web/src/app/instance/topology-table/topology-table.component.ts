import {Component, Input, OnInit} from '@angular/core';
import {GroupModule} from "../../_Module/GroupModule";
import {MatTableDataSource} from "@angular/material";
import {InstanceModule} from "../../_Module/InstanceModule";
import {Subscription} from "rxjs/Subscription";
import {OvserveFileService} from "../../_Service/ovserve-file.service";

@Component({
  selector: 'app-topology-table',
  templateUrl: './topology-table.component.html',
  styleUrls: ['./topology-table.component.sass']
})
export class TopologyTableComponent implements OnInit {
  dataSource = new MatTableDataSource<InstanceModule|any>([]);
  displayedColumns:string[] = ['name', 'description', 'compose', 'IP', 'option'];
  @Input('data') data:InstanceModule[];
  @Input('id') id:number;
  private subscription: Subscription;
  constructor(private _fileService: OvserveFileService) {
    this.subscription = this._fileService.instanceList$.subscribe( List => {
      if(List){
        this.setDataSource(List[this.id]);
      }
    });
  }

  ngOnInit() {
     this.setDataSource(this.data);
  }

  private setDataSource(data){
    this.dataSource.data = data;
  }

}
