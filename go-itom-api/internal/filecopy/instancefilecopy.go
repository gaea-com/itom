package filecopy

import (
	_sftp "github.com/pkg/sftp"
	"go-itom-api/pkg/config"
	"go-itom-api/pkg/sftp"
	"io"
)

func DownloadFromInstance(ip, path string) (file *_sftp.File, err error) {
	return sftp.Download(
		path,
		config.Config.System.RemoteUser,
		ip,
		config.Config.System.RemotePort,
		config.Config.System.PrivateKey)
}

func UploadToInstance(ip, path string, file io.Reader) (err error) {
	return sftp.Upload(
		path,
		config.Config.System.RemoteUser,
		ip,
		config.Config.System.RemotePort,
		config.Config.System.PrivateKey,
		file)
}
