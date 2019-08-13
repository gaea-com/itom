import { Component, OnInit } from '@angular/core';
import {BreadCrumbModule, IndexBreadCrumb} from "../_Module/BreadCrumbModule";
import {NavigationEnd, Router} from "@angular/router";
import {filter} from "rxjs/operators";
import {ApiService} from "../_Service/api.service";
import {AuthServiceService} from "../_Service/auth-service.service";

@Component({
  selector: 'app-index',
  templateUrl: './index.component.html',
  styleUrls: ['./index.component.sass']
})

export class IndexComponent implements OnInit {
  opened: boolean = true;
  active:string;
  title:string;
  breadcrumbs:BreadCrumbModule[] = [];
  role:string;
  constructor(private router:Router,
              private authService:AuthServiceService) {
    router.events.pipe(
      filter(event => event instanceof NavigationEnd)
    ).subscribe((val) => {
      if(val){
        this.getBreadCrumb(val);
      }
    })
  }

  ngOnInit() {
    this.role = this.authService.getRole();
  }

  sideBar(){
    this.opened = !this.opened;
  }

  getClass(params){
    return (params == this.active) ? 'active' : '';
  }

  private getBreadCrumb(val){
    let url = val.urlAfterRedirects;
    this.breadcrumbs = [];

    let bcArr = url.split('/');
    let rexg = /(.*)\?/;

    //如果最后一项的路径没有在字典里表示为参数 那么则取再前一项
    let urlStr = (IndexBreadCrumb[bcArr[bcArr.length-1]]) ? bcArr[bcArr.length-1] : bcArr[bcArr.length-2];

    let endPath:string;
    if(rexg.test(urlStr)){
      let matcher = rexg.exec(urlStr);
      endPath = matcher[1];
    }else{
      endPath = urlStr || 'project';
    }

    this.title = IndexBreadCrumb[endPath]['title'];
    this.active = endPath;

    if(IndexBreadCrumb[endPath]['path'].length > 0) {
      IndexBreadCrumb[endPath]['path'].forEach(prop => {
        this.breadcrumbs.push({
          link: prop,
          title: IndexBreadCrumb[prop]['title']
        })
      })
    }

    this.breadcrumbs.push({
      link: endPath,
      title: this.title
    });

  }
}
