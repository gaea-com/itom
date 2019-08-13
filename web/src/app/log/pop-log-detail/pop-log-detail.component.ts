import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef} from "@angular/material";
import {ProjectListComponent} from "../../project/project-list/project-list.component";

@Component({
  selector: 'app-pop-log-detail',
  templateUrl: './pop-log-detail.component.html',
  styleUrls: ['./pop-log-detail.component.sass']
})
export class PopLogDetailComponent implements OnInit {
  request:string;
  response:string;
  constructor(public dialogRef: MatDialogRef<ProjectListComponent>,
              @Inject(MAT_DIALOG_DATA) public data: any) { }

  ngOnInit() {
    this.request = this.data.request;
    this.response = this.data.response;
  }

}
