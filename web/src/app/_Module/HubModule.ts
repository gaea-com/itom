export class HubModule{
  public image:string;
  public status:boolean;
  constructor(prop:any){
    this.image = prop;
    this.status = false;
  }
}

export class HubData<E>{
  public option:HubModule[] = [];
  constructor(data:any){
    if(data && data.length > 0){
      data.forEach(prop => {
        this.option.push(new HubModule(prop));
      });
    }else{
      this.option = [];
    }
  }
}

export class HubTagModule{
  public tag:string;
  public version:string;
  constructor(prop: any, image:string){
    this.tag = image + ':' + prop;
    this.version = prop;
  }
}

export class HubTagData<E>{
  public option:HubTagModule[] = [];
  constructor(data:any, image:string){
    if(data && data.length > 0){
      data.forEach(prop => {
        this.option.push(new HubTagModule(prop, image));
      });
    }else{
      this.option = [];
    }
  }
}
