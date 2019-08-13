package snowflake

import (
	"sync"
	"time"

	"go-itom-api/pkg/config"
)

var sf *Flake
var st *Settings
var once sync.Once

// SnowFlake 获取唯一 Id 入口函数
func SnowFlake() *Flake {
	once.Do(func() {
		st = new(Settings)
		// 默认值：time.Date(2019, 6, 1, 0, 0, 0, 0, time.UTC)
		if config.Config.System.StartTime != "" {
			var err error
			st.StartTime, err = time.Parse(config.TimeItom, config.Config.System.StartTime)
			if err != nil {
				panic(err)
			}
		}else{
			st.StartTime = time.Date(2019, 6, 1, 0, 0, 0, 0, time.UTC)
		}
		st.MachineID = func() (uint16, error) {
			return config.Config.System.MachineID, nil
		}
		sf = NewFlake(*st)
		if sf == nil {
			panic("flake not created")
		}
	})
	return sf
}
