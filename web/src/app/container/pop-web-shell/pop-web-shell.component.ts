import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef} from "@angular/material";
import {ContainerListComponent} from "../container-list/container-list.component";

@Component({
  selector: 'app-pop-web-shell',
  templateUrl: './pop-web-shell.component.html',
  styleUrls: ['./pop-web-shell.component.sass']
})
export class PopWebShellComponent implements OnInit {

  constructor(public dialogRef: MatDialogRef<ContainerListComponent>,
              @Inject(MAT_DIALOG_DATA) public data: any,) { }

  ngOnInit() {
  }

  select(event){
    let val = event.value;
    let params = encodeURIComponent('target_ip='+
                                    this.data.ip +
                                    '&target_port=2375&container_id='+
                                    this.data.id +
                                    '&cmd=/bin/' + val);
    let url = 'webShell/' + params;
    window.open(url, '_blank');
    this.dialogRef.close();
  }
}
