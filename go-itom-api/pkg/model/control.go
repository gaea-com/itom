package model

//
//import (
//	"fmt"
//	logging "go-itom-api/pkg/logging"
//	"go-itom-api/pkg/mariadb"
//	"go-itom-api/pkg/util"
//	"reflect"
//)
//
//// 查询条件拼接
//func spliceQuery(args ...string) string {
//	var query string
//	for i, arg := range args {
//		if i == 0 {
//			query += fmt.Sprintf("%s=?", util.SnakeCasedName(arg))
//			continue
//		}
//		query += fmt.Sprintf(" AND %s=?", util.SnakeCasedName(arg))
//	}
//
//	return query
//}
//
//// 根据字段获取对应数据的参数
//func getArgsValue(data interface{}, args ...string) (
//	query, output string,
//	values []interface{},
//	err error) {
//
//	if len(args) == 0 {
//		err = fmt.Errorf("invalid Args (nil)")
//		return
//	}
//
//	query = spliceQuery(args...)
//	v := reflect.ValueOf(data)
//	if v.Kind() != reflect.Ptr {
//		err = fmt.Errorf("ptr type only")
//		return
//	}
//
//	values = make([]interface{}, 0)
//	var value interface{}
//	output = fmt.Sprintf("tableName:[%s]", v.Elem().Type().Name())
//	for _, arg := range args {
//		switch v.Elem().FieldByName(arg).Kind() {
//		case reflect.String:
//			value = v.Elem().FieldByName(arg).String()
//			output += fmt.Sprintf(" %s:[%s]", arg, value)
//
//		case reflect.Int:
//			value = v.Elem().FieldByName(arg).Int()
//			output += fmt.Sprintf(" %s:[%d]", arg, value)
//
//		case reflect.Invalid:
//			err = fmt.Errorf("reflect type Invalid: arg:[%s]\n", arg)
//			logging.Logger.Printf("data:[%+v] \n", data)
//			logging.Logger.Println(err.Error())
//			return
//
//		default:
//			err = fmt.Errorf("unknown reflect type: [%s]",
//				v.Elem().FieldByName(arg).Kind())
//			logging.Logger.Println(err.Error())
//			return
//		}
//		values = append(values, value)
//	}
//	return
//}
//
//// 数据库表增加一条数据
//// data 为传入的结构体(指针类型)
//// id 创建表单的 Id
//// args 为数据库查询的字段
//func addOneNewRecord(data interface{}, args ...string) (err error) {
//	// 拼接查询语句
//	var query, output string
//	var has bool
//	var values []interface{}
//	// 查询是否有旧数据存在
//	query, output, values, err = getArgsValue(data, args...)
//	if err != nil {
//		return
//	}
//	has, err = mariadb.DBEngine.Table(data).Where(query, values...).Exist()
//	if err != nil {
//		err = fmt.Errorf(dbRequireError, err.Error())
//		logging.Logger.Println(err.Error())
//		return
//	}
//	if has {
//		err = fmt.Errorf(dbRequireAlready, output)
//		logging.Logger.Println(err.Error())
//		return
//	}
//	_, err = mariadb.DBEngine.InsertOne(data)
//	if err != nil {
//		err = fmt.Errorf(dbRequireError, err.Error())
//		logging.Logger.Println(err.Error())
//		return
//	}
//	return
//}
//
//// 数据库表删除一条数据
//// data 为传入的结构体(指针类型)
//// args 为数据库查询的字段
//func deleteOneNewRecord(data interface{}, args ...string) (err error) {
//	// 拼接查询语句
//	var query string
//	var values []interface{}
//	var output string
//	query, output, values, err = getArgsValue(data, args...)
//	if err != nil {
//		return
//	}
//	_, err = mariadb.DBEngine.Table(data).Where(query, values...).Delete(data)
//	if err != nil {
//		err = fmt.Errorf(dbRequireError, output)
//		logging.Logger.Println(err.Error())
//		return
//	}
//	return
//}
//
//// 数据库表结构字段修改
//// data 为传入的结构体(指针类型)
//// args 为数据库查询的字段
//func updateOneNewRecord(data interface{}, index []string, args ...string) (err error) {
//
//	// 拼接查询语句
//	var query, output string
//	var values []interface{}
//	var has bool
//	query, output, values, err = getArgsValue(data, index...)
//	if err != nil {
//		return
//	}
//	has, err = mariadb.DBEngine.Table(data).Where(query, values...).Exist()
//	if err != nil {
//		err = fmt.Errorf(dbRequireError, output)
//		logging.Logger.Println(err.Error())
//		return
//	}
//	if !has {
//		err = fmt.Errorf(dbRequireNone, output)
//		logging.Logger.Println(err.Error())
//		return
//	}
//
//	// args 需要转换成蛇形表示
//	_args := make([]string, 0)
//	for _, arg := range args {
//		_args = append(_args, util.SnakeCasedName(arg))
//	}
//	_, err = mariadb.DBEngine.Table(data).Where(query, values...).Cols(_args...).Update(data)
//	if err != nil {
//		err = fmt.Errorf(dbRequireError, output)
//		logging.Logger.Println(err.Error())
//		return
//	}
//	return
//}
//
//// 数据库表查询数据是否存在
//func exist(data interface{}, args ...string) (exist bool, err error) {
//
//	// 拼接查询语句
//	var query, output string
//	var values []interface{}
//
//	// 查询是否有旧数据存在
//	query, output, values, err = getArgsValue(data, args...)
//	if err != nil {
//		return
//	}
//	exist, err = mariadb.DBEngine.Table(data).Where(query, values...).Exist()
//	if err != nil {
//		err = fmt.Errorf(dbRequireError, output)
//		logging.Logger.Println(err.Error())
//		return
//	}
//	return
//}
