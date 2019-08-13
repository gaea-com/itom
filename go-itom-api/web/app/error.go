package app

import (
	"io"
	"net/http"

	"github.com/gin-gonic/gin"
)

func checkErr(err error) (int, interface{}) {
	if err == io.EOF {
		return http.StatusBadRequest, gin.H{"message": "rece nil"}
	}
	return http.StatusBadRequest, gin.H{"message": err.Error()}
}
