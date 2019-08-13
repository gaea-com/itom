export class StopDockerModule{
  public name:string;
  public code:string;
  public closeTime:string;
  constructor(prop:any){
    this.name = prop.name;
    this.code = prop.id;
    this.closeTime = prop.FinishedAt;
  }
}

export class StopDockerData<E>{
  public option:StopDockerModule[] = [];
  constructor(data:any[]){
    if(data && data.length > 0){
      data.forEach(prop => {
        this.option.push(new StopDockerModule(prop));
      })
    }
  }
}
