import { Component, OnInit } from '@angular/core';
import {ActivatedRoute, NavigationEnd, Router} from "@angular/router";
import {filter} from "rxjs/operators";
import {BreadCrumbModule, IndexBreadCrumb} from "../_Module/BreadCrumbModule";
import {ToolsService} from "../_Service/tools.service";
import {ChatService} from "../_Service/chat.service";
import {AuthServiceService} from "../_Service/auth-service.service";

interface Nav {
  name:string;
  path:string;
  children ?: Nav[]
}

@Component({
  selector: 'app-detail',
  templateUrl: './detail.component.html',
  styleUrls: ['./detail.component.sass']
})
export class DetailComponent implements OnInit {
  projectName:string;
  pid:number|any = null;
  opened:boolean = true;
  active:string;
  title:string;
  breadcrumbs:BreadCrumbModule[] = [];
  constructor(private route: ActivatedRoute,
              private router:Router,
              private tools: ToolsService,
              private chatService: ChatService,
              private authService: AuthServiceService) {
    router.events.pipe(
      filter(event => event instanceof NavigationEnd)
    ).subscribe((val) => {
      if(val){
        this.getBreadCrumb(val);
      }
    })

  }

  ngOnInit() {
    this.route.params.subscribe(params => {
      console.log(params);
      if(params.params){
        this.tools.parseParams(params.params, (obj) => {
          this.projectName = obj['name'];
          this.pid = obj['code'];
        })
      }
    });

    let uid = this.authService.getUid();
  }

  getClass(params){
    return (params == this.active) ? 'active' : '';
  }

  sideBar(){
    this.opened = !this.opened;
  }

  private getBreadCrumb(val){
    let url = val.urlAfterRedirects;
    this.breadcrumbs = [];

    let bcArr = url.split('/');
    console.log(bcArr);
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
