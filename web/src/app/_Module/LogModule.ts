export class LogModule{
  public operate:string;
  public serverName:string;
  public startTime:number;
  public endTime:number;
  public userName:string;
  public taskId:string;
  public request:string;
  public response:string;
  constructor(prop:any){
    this.operate = prop.task_type;
    this.serverName = prop.instance_name;
    this.startTime = prop.create_at;
    this.endTime = prop.end_at;
    this.userName = prop.user_name;
    this.taskId = encodeURI(prop.task_id);
    this.request = prop.request;
    this.response = prop.result;
  }
}

export class LogData<E>{
  public option:LogModule[] = [];
  public length:number;
  constructor(data:any){
    if(data['pageData'] && data['pageData'].length > 0){
      data['pageData'].forEach(prop => {
        this.option.push(new LogModule(prop));
      });
      this.length = parseInt(data['totalCount']);
    }else{
      this.option = [];
    }
  }
}

export class LogDetailData<E>{
  public option:LogModule[] = [];
  constructor(data:any){
    if(data && data.length > 0){
      data.forEach(prop => {
        this.option.push(new LogModule(prop));
      });
    }else{
      this.option = [];
    }
  }
}
