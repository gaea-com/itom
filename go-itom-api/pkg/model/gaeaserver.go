package model

//import "time"
//
//type ServerStatus int
//type ServerIncludeType int
//
//const (
//	ServerOK           ServerStatus      = 200
//	ServerDelete       ServerStatus      = 400
//	ServerIncloudByCsv ServerIncludeType = 100
//)
//
//// GaeaServer gaea server
//type GaeaServer struct {
//	ID          int               `xorm:"int(10) notnull autoincr pk 'id'"`                // 实例ID
//	Name        string            `xorm:"varchar(120) notnull 'name'"`                     // 实例名称
//	InternalIP  string            `xorm:"varchar(255) notnull 'internal_ip'"`              // 服务器内网ip
//	PublicIP    string            `xorm:"varchar(255) notnull 'public_ip'"`                // 公网IP
//	CreateTime  time.Time         `xorm:"timestamp notnull created 'create_time'"`         // 创建时间
//	IncludeType ServerIncludeType `xorm:"smallint(3) notnull default(200) 'include_type'"` // 实例类型
//	Status      ServerStatus      `xorm:"smallint(3) notnull 'status'"`                    // 实例可用状态400删除200正常
//}
//
//// AddNewRecord 添加一条数据
//func (g *GaeaServer) AddNewRecord() error {
//	return addOneNewRecord(g, "InternalIP")
//}
//
//// DeleteOneRecord 删除一条数据
//func (g *GaeaServer) DeleteOneRecord(id int) error {
//	g.ID = id
//	return deleteOneNewRecord(g, "ID")
//}
//
//// UpdateOneRecord 更新一条数据
//func (g *GaeaServer) UpdateOneRecord(index []string, args ...string) error {
//	return updateOneNewRecord(g, index, args...)
//}
