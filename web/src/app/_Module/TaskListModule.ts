export class TaskModule{
  public id:number;
  public name;string;
  public description:string;
  public params:string;
  public scriptList:ScriptItemModule[];
  constructor(prop:any){
    this.id = prop.id;
    this.name = prop.name;
    this.params = encodeURI('id=' + prop['id']);
    this.description = prop.description;
    this.scriptList = this._getScriptList(prop);
  }

  private _getScriptList(prop){
    let result = [];
    if(prop['order_name'] && prop['order_name'].length > 0){
      prop['order_name'].forEach(item => {
        result.push(new ScriptItemModule(item));
      });
    }
    return result;
  }
}

export class ScriptItemModule{
  public id:number;
  public name:string;
  constructor(prop:any){
    this.id = prop.id;
    this.name = prop.name;
  }
}

export class ScriptListModule{
  public typeCode:number;
  public type:string;
  public name:string;
  public id:number;
  public itemType:string;
  public itemTypeCode:number;
  public group:any[];
  public iconStatus:boolean;
  constructor(type:number, scriptName:string, scriptId:number, itemType:number, list:any[]){
    this.typeCode = type;
    this.type = (type == 200) ? '容器' : '实例';
    this.name = (scriptName == '---') ? "暂无这个命令" : scriptName;
    this.id = scriptId;
    this.itemTypeCode = itemType;
    this.itemType = (!itemType) ? '全部' :
                        (itemType == 3) ? '自定义组':
                            (this.typeCode == 200) ? '容器' : '实例';
    this.group = this.getGroup(list);
    this.iconStatus = false;
  }

  private getGroup(list){
    let result = [];
    if(list && list.length > 0){
      list.forEach(prop => {
        let item = {};
        item['id'] = prop.id || prop.stype;
        item['name'] = (prop.name == '---') ? '暂无' : prop.name;
        result.push(item);
      });
    }

    return result;
  }
}

export class TaskData<E>{
  public option:TaskModule[] = [];
  constructor(data:any){
    if(data && data.length > 0){
      data.forEach(prop => {
        this.option.push(new TaskModule(prop));
      })
    }else{
      this.option = [];
    }
  }
}

export class TaskDetailModule{
  public id:number;
  public name;string;
  public description:string;
  constructor(prop:any) {
    this.id = prop.id;
    this.name = prop.name;
    this.description = prop.description;
  }
}

export class TaskDetailData<E>{
  public option:ScriptListModule[] = [];
  constructor(data:any){
    if(data && data.length > 0){
      data.forEach(prop => {
        Object.keys(prop).forEach(taskId => {
          let item = prop[taskId];
          let result = this.getResult(item);
          this.option.push(new ScriptListModule(type[item.type], item.order_name, item.order_id, scope[item.scope], result));
        });
      })
    }else{
      this.option = [];
    }
  }

  private getResult(item){
    let result = [];
    let type = scope[item.scope];
    switch(parseInt(type)){
      case 1:
        result = item.Group;
        break;
      case 2:
        result = item[item.scope];
        break;
      case 3:
        result = item.customerGroup;
        break;
      default:
        break;
    }
    return result;
  }
}

const type = {
  200: "100",
  300: "200"
}

const scope = {
  "all": '0',
  "Group": '1',
  "insList": '2',
  "dockList": '2',
  "customerGroup" : '3'
}
