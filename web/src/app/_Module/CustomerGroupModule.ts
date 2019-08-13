import {ComposeOptionMoudle} from "./ComposeModule";

export class CustomerGroupModule{
  public name:string;
  public description:string;
  public id:string;
  public type:string;
  public typeCode:number;
  public num:number;
  public params:string;
  public server:ServerItemModule[]|DockerItemModule[];
  constructor(prop:any){
    this.name = prop.name;
    this.description = prop.description;
    this.id = prop.id;
    this.type = (parseInt(prop.group_type) == 100) ? '实例组' : '容器组';
    this.typeCode = parseInt(prop.group_type);
    this.num = prop.server.length;
    this.params = encodeURI('id=' + prop['id'] + '&type=' + prop.group_type);
    this.server = this.getServer(prop.server, parseInt(prop.group_type));
  }

  public getServer(Arr, type){
    let result = [];
    Arr.forEach(prop => {
      let item = (type == 100) ? new ServerItemModule(prop) : new DockerItemModule(prop);
      result.push(item);
    });
    return result;
  }
}

export class ServerItemModule{
  public name:string;
  public id:string;
  constructor(prop:any){
    this.name = prop.name;
    this.id = prop.server_id;
  }
}

export class DockerItemModule{
  public name:string;
  public id:string;
  public serverId:string;
  constructor(prop:any){
    this.name = prop['docker_name'];
    this.id = prop['docker_id'];
    this.serverId = prop['server_id'];
  }
}

export class CustomerGroupData<E>{
  public option:CustomerGroupModule[] = [];
  constructor(data:any){
    if(data && data.length > 0){
      data.forEach(prop => {
        this.option.push(new CustomerGroupModule(prop));
      });
    }else{
      this.option = []
    }
  }
}
