import {InstanceModule} from "./InstanceModule";

export class TopologyData<E>{
  public option:any = {};
  constructor(data:any){
    if(data){
      data.forEach((prop) => {
        if(this.option[prop.group_id]){
          this.option[prop.group_id] = this.getGroupList(prop, this.option[prop.group_id]);
        }else{
          this.option[prop.group_id] = [];
          this.option[prop.group_id] = this.getGroupList(prop, []);
        }

      });
    }
  }

  private getGroupList(prop, Arr){
    Arr[Arr.length] = new InstanceModule(prop);
    return Arr
  }
}
