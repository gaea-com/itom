export class ImageModule{
  public image_id:string;
  public name:string;
  constructor(data:any){
    this.image_id = data.id;
    this.name = data['name_version'];
  }
}

export class ImageData<E>{
  option:ImageModule[] = [];
  constructor(data:any){
    if(data && data.length > 0){
      data.forEach(prop => {
        this.option.push(new ImageModule(prop));
      });
    }else{
      this.option = [];
    }
  }
}
