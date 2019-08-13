export class ContainerModule{
  public id:number;
  public name:string;
  public description:string;
  public statusCode:number;
  public status:string;
  public IP:string;
  public serverName:string;
  public image:string;
  public code:string;
  public serverId:string;

  constructor(prop:any){
    this.id = prop.id;
    this.name = prop.name;
    this.description = prop.description;
    this.statusCode = parseInt(prop.status);
    this.status = (parseInt(prop.status) == 200) ? '启用' : '停用';
    this.IP = prop.ip;
    this.serverName = prop.instance_name;
    this.image = prop.image_name;
    this.code = prop.container_id;
    this.serverId = prop.instance_id;
  }
}

export class ContainerData<E>{
  public option:ContainerModule[] = [];
  constructor(data:any){
    if(data && data.length > 0){
      data.forEach(prop => {
        this.option.push(new ContainerModule(prop));
      });
    }else{
      this.option = [];
    }
  }
}
