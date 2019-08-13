package util

import (
	"testing"
)

func TestUtc2Bj(t *testing.T) {
	t.Log(Utc2Bj("2015-01-06T15:47:32.080254511Z"))
	t.Log(Utc2Bj(""))
}

func TestString2Int(t *testing.T) {
	t.Log(String2Int("123123"))
}

func TestStringLeftTrim(t *testing.T) {
	t.Log(StringLeftTrim("^sha256:", "sha256:aasdf90*(*(234fasdfas"))
	t.Log(StringLeftTrim("^sha256:", "aasdf90*(*(234fasdfas"))
}
