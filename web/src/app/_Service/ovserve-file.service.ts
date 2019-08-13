 import { Injectable } from '@angular/core';
import {Subject} from "rxjs/Subject";
import {DropMenuComponent} from "../instance/drop-menu/drop-menu.component";
import {TopologyTableComponent} from "../instance/topology-table/topology-table.component";
 import {TaskCreateComponent} from "../task/task-create/task-create.component";

@Injectable()
export class OvserveFileService {
  private observeFileChange = new Subject<DropMenuComponent>();
  fileChange$ = this.observeFileChange.asObservable();

  private observeInstanceList = new Subject<TopologyTableComponent>();
  instanceList$ = this.observeInstanceList.asObservable();

  private observeTaskValue = new Subject<TaskCreateComponent>();
  taskValue$ = this.observeTaskValue.asObservable();

  public setFile(){
    this.observeFileChange.next();
  }

  public setInstanceList(msg){
    this.observeInstanceList.next(msg);
  }

  public setTaskList(msg){
    this.observeTaskValue.next(msg);
  }
}
