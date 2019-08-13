import { Component } from '@angular/core';
import {ApiService} from "./_Service/api.service";

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.sass']
})
export class AppComponent {
  title = 'itom';

  constructor(private apiService: ApiService){
    this.apiService.getProject({})
  }
}
