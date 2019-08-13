export class MessageModule{
  public event?:string;
  public eventName:string;
  public url:string;
  public ID?:string;
  public msg?:string;
  public statusCode:number;
  public status?:string;
  public line:boolean;
  public isEnd:boolean;
  public isShowButton:boolean;
  public taskId:string;

  constructor(prop:any, config:any, url){
    this.event = prop.event;
    this.eventName = config.getEventName(prop.event);
    this.url = url;
    this.ID = prop.msg.id;
    this.isEnd = (prop.msg.total || prop.msg.error) ? true : false
    this.msg = (!this.isEnd) ? prop.msg.msg :
                  (prop.msg.status == 200) ? prop.msg.total : prop.msg.error;
    this.statusCode = prop.msg.status;
    this.status = (prop.msg.status == 200) ? '成功' : '失败';
    this.line = prop.line || false;
    this.isShowButton = (url == 'task') ? true : false;
    this.taskId = prop.msg.id;
  }
}


export class MessageHashMoudle{
  public ID:string;
  public group:MessageModule[];
}
