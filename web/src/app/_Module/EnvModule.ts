export class EnvModule{
  public docker_name:string;
  public docker_description:string;
  public image_name:string;
  public envList:EnvItemModule[];
  public volumeList:EnvItemModule[];

  constructor(prop:any, name?:string, isInit?:boolean){
    this.image_name = name;
    this.docker_name = prop.name;
    this.docker_description = prop.description;
    this.envList = this.getList(prop['data'].env, isInit);
    this.volumeList = this.getList(prop['data'].data, isInit);
  }

  private getList(item:any, isInit){
    console.log(item);
    let result:EnvItemModule[] = [];
    if(isInit){
      item.forEach(prop => {
        result.push(new EnvItemModule(prop));
      });
    }else{
      if(item){
        Object.keys(item).forEach(prop => {
          result.push(new EnvItemModule({
            'Key': prop,
            'Val': item[prop] || ''
          }));
        })
      }else{
        result.push(new EnvItemModule({
          'Key': '',
          'Val': ''
        }));
      }
    }

    return result;
  }
}

export class EnvItemModule{
  public Key:string;
  public Value:string;
  constructor(prop){
    this.Key = prop.Key;
    this.Value = prop.Val;
  }
}

export class EnvData<E>{
  public option:any = {};
  public num:number = 0;
  constructor(data:any){
    if(data){
      Object.keys(data).forEach(field => {
        this.num++;
        if(typeof data[field]['data'] == 'string'){
          let item = this.getDefaultItem(field);
          this.option[field] = new EnvModule(item, field, true);
        }else{
          this.option[field] = new EnvModule(data[field], field, false);
        }
      });
    }else {
      this.option = {};
    }
  }

  private getDefaultItem(name){
    return {
      "name": '',
      "description": '',
      "image_name": name,
      "env": [{
        "Key": '',
        "Val": ''
      }],
      "data": [{
        "Key": '',
        "Val": ''
      }]
    }
  }
}
