import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
  name: 'status'
})
export class StatusPipe implements PipeTransform {

  transform(value: any, args?: any): any {
    let status:string;
    switch(value){
      case 100:
        status = '创建';
        break;
      case 200:
        status = '正常';
        break;
      case 300:
        status = '停止';
        break;
      default:
        status = '删除';
        break;
    }
    return status;
  }


}
