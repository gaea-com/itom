export class InstanceModule{
  public id:number;
  public name:string;
  public description:string;
  public compose:string;
  public compose_id:string;
  public public_ip:string;
  public internal_ip:string;
  public dockerNumber:number;
  public isUploadImage:boolean;
  public params:string;
  public imageList:string[];
  public cds:number;
  public ram:number;
  public cpu:number;
  public imageNum:number;

  constructor(prop:any){
    this.id = prop['server_id'];
    this.name = prop['name'];
    this.description = prop['description'];
    this.compose = prop['compose_name'];
    this.compose_id = prop['compose_id'];
    this.public_ip = prop['public_ip'];
    this.internal_ip = prop['internal_ip'];
    this.dockerNumber = prop['docker'].length;
    this.isUploadImage = (prop['image_name'].length > 0) ? true : false;
    this.params = encodeURI('sid=' + prop['server_id'] +
                            '&name=' + prop['name'] +
                            '&cid=' + prop['compose_id'] +
                            '&ip=' + prop['internal_ip']);
    this.imageList = prop['image_name'];
    this.cpu = prop['cpu'];
    this.cds = prop['cds'];
    this.ram = prop['ram'];
    this.imageNum = prop['image_name'].length;
  }
}

export class InstanceData<E>{
  public option:InstanceModule[] = [];
  // public dict:any = {};
  constructor(data:any){
    if(data && data.length > 0){
      data.forEach(prop => {
        this.option.push(new InstanceModule(prop));
        // let label = 'row_' + prop.server_id;
        // this.dict[label] = new InstanceModule(prop);
      });
    }else{
      this.option = [];
      // this.dict = {};
    }
  }

}
