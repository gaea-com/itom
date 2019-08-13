export class ComposeModule{
  public name:string;
  public description:string;
  public id:number;
  public imageList:string[];
  public status:number;
  public detail:ComposeItemModule[];

  constructor(prop:any){
    this.name = prop['name'];
    this.description = prop['description'];
    this.id = prop['id'];
    this.imageList = prop['image_name'];
    this.detail = this.getDetail(prop['image_times']);
    this.status = prop['status'];
  }

  public getDetail(prop){
    let result:ComposeItemModule[] = [];
    if(prop.length > 0){
      prop.forEach(item => {
        let detailItem:any = {};
        detailItem['image'] = item['image_name'];
        detailItem['sleep_time'] = item['sleep_time'];
        result.push(detailItem);
      });
    }

    return result
  }
}

export class ComposeOptionMoudle{
  public name:string;
  public id:string;
  constructor(prop:any){
    this.name = prop.name;
    this.id = prop.id;
  }
}

export class ComposeItemModule{
  image:string;
  sleep_time:number;
}

export class ComposeData<E>{
  public data:ComposeModule[] = [];
  public option:ComposeOptionMoudle[] = [];
  constructor(data:any[]){
    if(data){
      data.forEach(prop => {
        this.data.push(new ComposeModule(prop))
        if(prop.status == 200){
          this.option.push(new ComposeOptionMoudle(prop));
        }
      });
    }else{
      this.data = [];
      this.option = [];
    }
  }
}
