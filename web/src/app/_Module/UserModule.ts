export class UserModule{
  public id:number;
  public name:string;
  public type:string;
  public typeCode:string;
  public status:string;
  public statusCode:number;
  public loginTime:string;
  public registerTime:string;
  constructor(prop:any){
    this.id = prop.id;
    this.name = prop.name;
    this.type = (prop.type == 'root') ? '高级管理员' : '普通用户';
    this.typeCode = prop.type;
    this.status = (parseInt(prop.status) == 200) ? '正常' :
                      (parseInt(prop.status) == 300) ? '异常或冻结' : '登录错误';
    this.statusCode = parseInt(prop.status);
    this.loginTime = prop.login_time;
    this.registerTime = prop.reg_time;
  }
}

export class UserData<E>{
  public option:UserModule[] = [];
  constructor(data:any){
    if(data && data.length > 0){
      data.forEach(prop => {
        this.option.push(new UserModule(prop));
      })
    }else{
      this.option = [];
    }
  }
}
