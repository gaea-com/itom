export class CronJobModule{
  public id:number;
  public name:string;
  public description:string;
  public params:string;
  public type:string;
  public typeCode:number;
  public condition:any;
  public taskId:number;
  public taskName:string;
  constructor(prop:any){
    this.id = prop.id;
    this.name = prop.name;
    this.description = prop.description;
    this.params = encodeURI('id=' + prop['id']);
    this.typeCode = prop.type;
    this.type = (prop.type == 100) ? '单次' : '重复';
    this.condition = (prop.type == 100) ? parseInt(prop['run_condition']) : prop['run_condition'];
    this.taskId = prop['task_id'];
    this.taskName = prop['task_name'];
  }
}

export class CronJobData<E>{
  public option:CronJobModule[] = [];
  constructor(data:any){
    if(data && data.length > 0){
      data.forEach(prop => {
        this.option.push(new CronJobModule(prop));
      })
    }else{
      this.option = [];
    }
  }
}
