package model

//import (
//	"fmt"
//	"go-itom-api/pkg/mariadb"
//	"time"
//)
//
//// Project 项目管理表
//type Project struct {
//	ID               int       `xorm:"bigint(10) notnull autoincr pk 'id'"`                   // 项目ID
//	Name             string    `xorm:"varchar(255) notnull 'name'"`                           // 项目名称
//	RemoteID         string    `xorm:"varchar(255) notnull 'remote_id'"`                      // 游戏远程id
//	CreateUser       int       `xorm:"bigint(10) unsigned notnull autoincr pk 'create_user'"` // 项目创建人
//	CreateAt         time.Time `xorm:"datetime notnull 'create_at'"`                          // 项目创建时间
//	Status           int       `xorm:"smallint(3) unsigned notnull 'status'"`                 // 项目状态
//	ProjectDescription string  `xorm:"varchar(255) notnull 'project_descption'"`              // 项目描述
//}
//
//// AddNewRecord 添加一条数据
//func (p *Project)AddNewRecord() error {
//	return addOneNewRecord(p, "Name")
//}
//
//// DeleteOneRecord 删除一条数据
//func (p *Project)DeleteOneRecord(id int) error {
//	p.ID = id
//	return deleteOneNewRecord(p, "ID")
//}
//
//// UpdateOneRecord 更新一条数据
//func (p *Project)UpdateOneRecord(index []string, args ...string) error {
//	return updateOneNewRecord(p, index, args...)
//}
//
//// IsExistByID 根据Id查询实例是否存在
//func (p *Project)IsExistByID(id int) (exist bool, err error) {
//	exist, err = mariadb.DBEngine.Where("id=?", id).Exist(p)
//	if err != nil {
//		err = fmt.Errorf(dbRequireError, err.Error())
//		return
//	}
//	return
//}
