import {Inject, Injectable} from '@angular/core';
import {
  HttpClient,
  HttpErrorResponse,
  HttpEvent,
  HttpHandler,
  HttpHeaders,
  HttpInterceptor,
  HttpRequest
} from "@angular/common/http";
import {DOCUMENT} from "@angular/common";

const httpOptions = {
  headers: new HttpHeaders({
    'Content-Type': 'application/x-www-form-urlencoded'
  })
}

@Injectable()
export class ApiService {
  private domain: string;
  public base:string;
  constructor(private http: HttpClient,
              @Inject(DOCUMENT) private document: any) {
    this.domain = this.document.location.hostname;
    this.base = this.document.location.protocol + '//' + this.document.location.hostname + '/api/';
  }

  getBaseUrl(){
    return this.base
  }

  getJwt(){
    let uri = this.base + 'getjwt';
    return this.http.get(uri)
  }

  verifycode(formData){
    let uri = this.base + 'verifycode';
    return this.http.post(uri, formData, httpOptions)
  }

  login(formData){
    let uri = this.base + 'login';
    return this.http.post(uri, formData, httpOptions)
  }

  logout(){
    let uri = this.base + 'logout';
    return this.http.post(uri, {}, httpOptions)
  }

  getProject(formData){
    let uri = this.base + 'project';
    return this.http.get(uri, {params: formData})
  }

  postProject(formData){
    let uri = this.base + 'project';
    return this.http.post(uri, formData, httpOptions)
  }

  putProject(formData, id){
    let uri = this.base + 'project/' + id;
    return this.http.put(uri, formData, httpOptions)
  }

  delProject(id){
    let uri = this.base + 'project/' + id;
    return this.http.delete(uri)
  }

  updateHarbor(formData){
    let uri = this.base + 'updateimages';
    return this.http.post(uri, formData, httpOptions)
  }

  deleteHarbor(formData){
    let uri = this.base + 'hub';
    httpOptions['body'] = formData;
    return this.http.delete(uri, httpOptions)
  }

  userResetPassword(formData){
    let uri = this.base + 'resetpasswd';
    return this.http.post(uri, formData, httpOptions)
  }

  getUserProject(formData){
    let uri = this.base + 'getaccredit';
    return this.http.get(uri, {params: formData})
  }

  deleteUser(id){
    let uri = this.base + 'user/' + id;
    return this.http.delete(uri, httpOptions)
  }

  perm(formData){
    let uri = this.base + 'createaccredit';
    return this.http.post(uri, formData, httpOptions)
  }

  deletePerm(formData){
    let uri = this.base + 'deleteaccredit';
    return this.http.post(uri, formData, httpOptions)
  }

  getHubList(formData){
    let uri = this.base + 'hublist';
    return this.http.get(uri, {params: formData})
  }

  getTagList(formData){
    let uri = this.base + 'hubtaglist';
    return this.http.get(uri, {params: formData})
  }

  getUser(){
    let uri = this.base + 'user';
    return this.http.get(uri)
  }

  updateUser(formData, userId){
    let uri = this.base + 'user/' + userId;
    return this.http.put(uri, formData, httpOptions)
  }

  createUser(formData){
    let uri = this.base + 'user';
    return this.http.post(uri, formData, httpOptions)
  }

  resetPassword(formData){
    let uri = this.base + 'rootreset';
    return this.http.post(uri, formData, httpOptions)
  }

  checkHub(){
    let uri = this.base + 'hubcheck';
    return this.http.get(uri)
  }

  testHub(formData){
    let uri = this.base + 'hubconn';
    return this.http.post(uri, formData, httpOptions)
  }

  saveHubConfig(formData){
    let uri = this.base + 'hub';
    return this.http.post(uri, formData, httpOptions)
  }

  ////////////////////////////////////////////////////////////
  getProjectUser(pid){
    let uri = this.base + 'user';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.get(uri, httpOptions)
  }


  createGroup(formData, pid){
    let uri = this.base + 'group';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  deleteGroup(id, pid){
    let uri = this.base + 'group/' + id;
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.delete(uri, httpOptions)
  }

  getTopologyList(formData, pid){
    let uri = this.base + 'cloud';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    httpOptions['params'] = formData;
    return this.http.get(uri, httpOptions)
  }

  getGroupList(id){
    let uri = this.base + 'group/'+ id;
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', id);
    return this.http.get(uri, httpOptions)
  }

  linkGroup(formData, pid){
    let uri = this.base + 'bindgroup';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  copyGroup(formData, pid){
    let uri = this.base + 'copygroup';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  importInstance(formData, pid, callback){
    let uri = this.base + 'instanceinclude';
    let request = new HttpRequest('POST', uri, formData, {
      reportProgress: true,
      headers: new HttpHeaders({
        'Access-Control-Allow-Project': pid
      })
    })
    this.http.request(request)
      .subscribe(event => {callback(event)}, error => {callback(error)});
  }

  getCompose(formData, pid){
    let uri = this.base + 'dockercompose';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    httpOptions['params'] = formData;
    return this.http.get(uri, httpOptions)
  }

  createComponse(formData, pid){
    let uri = this.base + 'dockercompose';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  putComponse(formData, id, pid){
    let uri = this.base + 'dockercompose/' + id;
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.put(uri, formData, httpOptions)
  }

  statusCompose(formData, id, pid){
    let uri = this.base + 'dockercompose/' + id;
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.put(uri, formData, httpOptions)
  }

  loadImage(formData, pid){
    let uri = this.base + 'batchpullimages';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions);
  }

  getEnvImageList(formData, pid){
    let uri = this.base + 'serverimages/';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  getEnvItem(formData, pid){
    let uri = this.base + 'getenv/';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  updateEnvItem(formData, pid){
    let uri = this.base + 'createenv/';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  deleteServer(formData, pid){
    let uri = this.base + 'deleteserver';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions);
  }

  runContainer(formData, pid){
    let uri = this.base + 'batchcreatecans';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  getContainer(formData, pid){
    let uri = this.base + 'containers/';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  stopContainer(formData, pid){
    let uri = this.base + 'stopcan';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  getStopContainer(formData, pid){
    let uri = this.base + 'stopcans';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  stopContainerForServer(formData, pid){
    let uri = this.base + 'stopcanforserver';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  updateContainer(formData, pid){
    let uri = this.base + 'updatecans';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  sendCmd(formData, pid){
    let uri = this.base + 'cancmd';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  uploadToDocker(formData, callback){
    let uri = this.base + 'docker_upload';
    let request = new HttpRequest('POST', uri, formData, {
      reportProgress: true
    })
    this.http.request(request)
      .subscribe(event => {callback(event)}, error => {callback(error)});
  }

  createScript(formData, pid){
    let uri = this.base + 'createorderinfo';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  editScript(formData, pid){
    let uri = this.base + 'updateorderinfo';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  getScript(formData, pid){
    let uri = this.base + 'orderinfo';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    httpOptions['params'] = formData;
    return this.http.get(uri, httpOptions)
  }

  deleteScript(formData, pid){
    let uri = this.base + 'delorderinfo';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  runScriptToInstance(formData, pid){
    let uri = this.base + 'cmd';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  runScriptToDocker(formData, pid){
    let uri = this.base + 'cancmd';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  createCustomerGroup(formData, pid){
    let uri = this.base + 'createcustomgroup';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  getCustomerGroup(formData, pid){
    let uri = this.base + 'customgroup';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    httpOptions['params'] = formData;
    return this.http.get(uri, httpOptions);
  }

  deleteCustomerGroup(formData, pid){
    let uri = this.base + 'deletecustomgroup';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  updateCustomerGroup(formData, pid){
    let uri = this.base + 'updatecustomgroup';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  createTask(formData, pid){
    let uri = this.base + 'createtaskinfo';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions);
  }

  updateTask(formData, pid){
    let uri = this.base + 'updatetaskinfo';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions);
  }

  getTask(formData, pid){
    let uri = this.base + 'gettaskinfo';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    httpOptions['params'] = formData
    return this.http.get(uri, httpOptions)
  }

  getTaskDetail(formData, pid){
    let uri = this.base + 'getorderbytask';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    httpOptions['params'] = formData;
    return this.http.get(uri, httpOptions)
  }

  deleteTask(formData, pid){
    let uri = this.base + 'deltaskinfo';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  runTask(formData, pid){
    let uri = this.base + 'task';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  createCronJob(formData, pid){
    let uri = this.base + 'createtimedtask';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  getCronJob(formData, pid){
    let uri = this.base + 'timedtask';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    httpOptions['params'] = formData;
    return this.http.get(uri, httpOptions)
  }

  deleteCronJob(formData, pid){
    let uri = this.base + 'deltimedtask';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  updateCronJob(formData, pid){
    let uri = this.base + 'updatetimedtask';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  sendCmdToInstance(formData, pid){
    let uri = this.base + 'cmd';
    httpOptions.headers = httpOptions.headers.set('Access-Control-Allow-Project', pid);
    return this.http.post(uri, formData, httpOptions)
  }

  getLog(formData){
    let uri = this.base + 'operatelog';
    return this.http.post(uri, formData, httpOptions)
  }
}
