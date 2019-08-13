package util

import (
	"regexp"
	"strconv"
	"time"
)

// Utc2Bj utc 时间转 北京时间 (时间+8 小时)
func Utc2Bj(utc string) string {
	utcTime, _ := time.Parse(time.RFC3339Nano, utc)
	return utcTime.Add(time.Hour * 8).Format("2006-01-02 15:04:05")
}

// TimeDuration2Ptr int类型转换成go的time.Duration指针(单位：秒)
func TimeDuration2Ptr(t int) *time.Duration {
	i := time.Second * time.Duration(t)
	return &i
}

// String2Int string类型数字转换成int类型
func String2Int(s string) int {
	i, _ := strconv.Atoi(s)
	return i
}

// String2Ptr string类型转换成指针类型
func String2Ptr(s string) *string {
	return &s
}

// SnakeCasedName 转化为蛇形命名方式
func SnakeCasedName(name string) string {
	newstr := make([]rune, 0)
	for idx, chr := range name {
		if isUpper := 'A' <= chr && chr <= 'Z'; isUpper {
			if idx > 0 {
				newstr = append(newstr, '_')
			}
			chr -= ('A' - 'a')
		}
		newstr = append(newstr, chr)
	}
	return string(newstr)
}

func StringLeftTrim(reg, s string) string {
	if regexp.MustCompile(reg).MatchString(s) {
		s = s[len(reg)-1:]
	}
	return s
}
