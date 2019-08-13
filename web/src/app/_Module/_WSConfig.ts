import {Inject} from "@angular/core";
import {DOCUMENT} from "@angular/common";

export class EventConfigModule{
  public event: EventItemConfig
}

export class EventItemConfig{
  public title: string;
  public method:string;
  constructor(prop:any){
    this.title = prop.title;
    this.method = prop.url;
  }
}

export const WSConfigModule = {
  'image_pull': {
    title: '拉取镜像',
    url: 'task',
    result: false
  },
  'instance_include': {
    title: '导入实例',
    url: 'topology',
    result: false
  },
  'container_create': {
    title: ' 创建容器',
    url: 'container',
    result: false
  },
  'container_toggle': {
    title: ' 关闭容器',
    url: 'container',
    result: false
  },
  'container_list':{
    title: '容器更新列表',
    url: 'container',
    result: false
  },
  'container_cmd':{
    title: '向容器发送命令',
    url: 'task',
    result: true
  },
  'task':{
    title: '任务',
    url: 'task',
    result: true
  },
  'command':{
    title: '向实例发送命令',
    url: 'task',
    result: true
  },
  'image_list':{
    title: '更新镜像',
    url: 'instance',
    result: false
  }
}

export class EventData<E>{
  public option: any = {};
  constructor(data:any){
    Object.keys(data).forEach(prop => {
      this.option[prop] = new EventItemConfig(data[prop]);
    })
  }

  public getEventName(event){
    return this.option[event].title;
  }

  public getEventMethod(event){
    return this.option[event].method //(this.option[event].method) ? 'logDetail' : this.option[event].url;
  }

  public isEnd(prop){
    return (prop.msg.error || prop.msg.step_no) ? true : false
  }
}
