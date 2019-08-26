import {Component, Inject, OnInit} from '@angular/core';
import {ActivatedRoute} from "@angular/router";
import {Terminal} from 'xterm';
import {fit} from 'xterm/lib/addons/fit/fit';
import {ToolsService} from "../../_Service/tools.service";

@Component({
  selector: 'app-web-shell',
  templateUrl: './web-shell.component.html',
  styleUrls: ['./web-shell.component.sass']
})
export class WebShellComponent implements OnInit {
  params:any;
  socket:any;
  xterm:any;
  height:string;
  public url:string;
  constructor(private route:ActivatedRoute,
              public tools:ToolsService,
              @Inject(DOCUMENT) private document: any) {
    this.url = 'ws://' + this.document.location.hostname;
    this.route.params.subscribe( params => {
      if(params.params){
        this.tools.parseParams(params.params, (obj) => {
          this.params = obj;
          let cols = Math.floor(window.outerWidth / 9);
          let rows = Math.floor(window.outerHeight/20) - 5;

          setTimeout(()=>{
            this.runXterm(cols, rows, obj);
          }, 500)
        })
      }
    });
  }

  ngOnInit() {
    this.height = window.outerHeight - 75 + 'px';
  }

  private runXterm(cols, rows, params){
    this.xterm = new Terminal();
    this.xterm.open(document.getElementById('webShellWrapper'));
    fit(this.xterm);

    let url = this.url + '/ws?' +
              '&target_ip=' + params.target_ip +
              '&target_port='+ params.target_port +
              '&container_id='+ params.container_id +
              '&rows='+ rows +
              '&cols=' + cols +
              '&cmd=' + params.cmd;
    this.socket = new WebSocket(url);

    this.xterm.on('data', (data) => {
      this.socket.send(data);
    });

    this.socket.onmessage = (msg) => {
      this.xterm.write(msg.data);
    }
  }

}
