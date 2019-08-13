export class ScriptModule{
  public name:string;
  public id:number;
  public type:string;
  public typeCode:string;
  public description:string;
  public script:string;
  public canRun:boolean;   //控制能否运行
  public userName:string;
  public userId:number;
  public params:string;
  public canShare:boolean;   //控制是否显示

  constructor(prop:any){
    this.name = prop.name;
    this.id = prop.id;
    this.type = (parseInt(prop.type) == 200) ? '实例' : '容器';
    this.typeCode = prop.type;
    this.description = prop.description;
    this.script = prop.order;
    this.canRun = prop.userpermission;
    this.canShare = (prop.update_status == '200') ? true : false;
    this.userName = prop.user_name;
    this.userId = parseInt(prop.create_user);
    this.params = encodeURI('id=' + prop['id']);
  }
}

export class ScriptData<E>{
  public option:ScriptModule[] = [];
  constructor(data:any){
    if(data && data.length > 0){
      data.forEach(prop => {
        this.option.push(new ScriptModule(prop));
      });
    }else{
      this.option = [];
    }
  }
}
