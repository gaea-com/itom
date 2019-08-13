export class ProjectModule{
  public name:string;
  // public status:number;
  public description:string;
  public id:number;
  // public gameCode:string;
  public params:string;
  constructor(prop:any){
    this.name = prop.name;
    // this.status = parseInt(prop['status']);
    this.description = prop['project_descption'];
    this.id = prop['id'];
    // this.gameCode = prop['remote_id'];
    this.params = encodeURI('pid=' + prop['id'] + '&name=' + prop['name']);
  }
}

export class ProjectData<E>{
  public option:ProjectModule[] = [];

  constructor(data:any[]){
    if(data){
      data.forEach(prop => {
        this.option.push(new ProjectModule(prop));
      })
    }else{
      this.option = []
    }
  }
}
