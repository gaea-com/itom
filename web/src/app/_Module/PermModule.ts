export class PermModule{
  public projectcode:number;
  public project:string;
  public userId:number;
  public user:string;
  public createTime:string;
  constructor(prop:any){
    this.projectcode = prop.project_id;
    this.project = prop.project_name;
    this.userId = prop.user_id;
    this.user = prop.user_name;
    this.createTime = prop.create_at;
  }
}

export class PermData<E>{
  public option:PermModule[] = [];
  constructor(data:any){
    if(data && data.length > 0){
      data.forEach(prop => {
        this.option.push(new PermModule(prop));
      })
    }else{
      this.option = [];
    }
  }
}
