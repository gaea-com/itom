import {InstanceModule} from "./InstanceModule";

export class GroupModule{
  public id:number;
  public name:string;
  // public type:number;
  public iconStatus:boolean;
  public group:InstanceModule[];

  constructor(prop:any){
    this.id = prop.id;
    this.name = prop.name;
    // this.type = prop.type;
    this.iconStatus = true;
    this.group = [];
  }
}


export class GroupData<E>{
  public option:any = {};
  public ids:number[] = [];
  constructor(data:any){
    if(data){
      data.forEach((prop) => {
        this.ids.push(prop.id);
        this.option[prop.id] = new GroupModule(prop);
      })
    }else{
      this.option = {};
    }
  }
}
