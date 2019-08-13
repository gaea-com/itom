package sftp

import (
	"fmt"
	"github.com/pkg/sftp"
	"golang.org/x/crypto/ssh"
	"io"
	"net"
	"time"
)

// NewSftpClient
// 初始化生成一个 sftp 客户端
func NewSftpClient(user, host string, port int, priviteKey []byte) (*sftp.Client, error) {
	var (
		auth         []ssh.AuthMethod
		addr         string
		clientConfig *ssh.ClientConfig
		sshClient    *ssh.Client
		sftpClient   *sftp.Client
		err          error
	)
	// get auth method
	auth = make([]ssh.AuthMethod, 0)
	siger, err := ssh.ParsePrivateKey(priviteKey)
	if err != nil {
		return nil, err
	}
	auth = append(auth, ssh.PublicKeys(siger))
	clientConfig = &ssh.ClientConfig{
		User:    user,
		Auth:    auth,
		Timeout: 30 * time.Second,
		HostKeyCallback: func(hostname string, remote net.Addr, key ssh.PublicKey) error {
			return nil
		},
	}
	// connet to ssh
	addr = fmt.Sprintf("%s:%d", host, port)
	if sshClient, err = ssh.Dial("tcp", addr, clientConfig); err != nil {
		return nil, err
	}
	// create sftp client
	if sftpClient, err = sftp.NewClient(sshClient); err != nil {
		return nil, err
	}
	return sftpClient, nil
}

// Download
// 获取远端文件的内容
func Download(path, user, host string, port int, privateKey []byte) (file *sftp.File, err error) {
	var sftpClient *sftp.Client
	sftpClient, err = NewSftpClient(user, host, port, privateKey)
	if err != nil {
		err = fmt.Errorf("sftp.init.error [%s]", err.Error())
		return
	}
	defer sftpClient.Close()
	file, err = sftpClient.Open(path)
	if err != nil {
		err = fmt.Errorf("sftp.open.error [%s]", err.Error())
		return
	}
	return
}

// Upload
// 本地文件上传至远端服务器
func Upload(path, user, host string, port int, privateKey []byte, srcFile io.Reader) (err error) {
	var sftpClient *sftp.Client
	sftpClient, err = NewSftpClient(user, host, port, privateKey)
	if err != nil {
		err = fmt.Errorf("sftp.init.error [%s]", err.Error())
		return
	}
	defer sftpClient.Close()
	var destFile *sftp.File
	destFile, err = sftpClient.Create(path)
	if err != nil {
		err = fmt.Errorf("sftp.createFile.error [%s]", err.Error())
		return
	}
	_, err = io.Copy(destFile, srcFile)
	if err != nil {
		err = fmt.Errorf("sftp.copy.error [%s]", err.Error())
		return
	}
	return
}
