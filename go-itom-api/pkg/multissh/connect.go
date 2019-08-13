package multissh

import (
	"bytes"
	"fmt"
	"io/ioutil"

	"net"
	"strconv"
	"time"

	"golang.org/x/crypto/ssh"
)

type SshHost struct {
	Host      string
	Port      int
	Username  string
	Password  string
	CmdFile   string
	Cmds      string
	CmdList   []string
	Key       string
	LinuxMode bool
	Result    SshResult
}

type SshResult struct {
	Host    string
	Success bool
	Result  string
}

func initSshConnect(user, password, host, key string, port int) (*ssh.Session, error) {
	var (
		auth         []ssh.AuthMethod
		addr         string
		clientConfig *ssh.ClientConfig
		client       *ssh.Client
		session      *ssh.Session
		err          error
	)
	auth = make([]ssh.AuthMethod, 0)
	if key == "" {
		auth = append(auth, ssh.Password(password))
	} else {
		pemBytes, err := ioutil.ReadFile(key)
		if err != nil {
			return nil, err
		}

		var signer ssh.Signer
		if password == "" {
			signer, err = ssh.ParsePrivateKey(pemBytes)
		} else {
			signer, err = ssh.ParsePrivateKeyWithPassphrase(pemBytes, []byte(password))
		}
		if err != nil {
			return nil, err
		}
		auth = append(auth, ssh.PublicKeys(signer))
	}

	clientConfig = &ssh.ClientConfig{
		User:    user,
		Auth:    auth,
		Timeout: 30 * time.Second,
		HostKeyCallback: func(hostname string, remote net.Addr, key ssh.PublicKey) error {
			return nil
		},
	}
	addr = fmt.Sprintf("%s:%d", host, port)
	if client, err = ssh.Dial("tcp", addr, clientConfig); err != nil {
		return nil, err
	}
	// create session
	if session, err = client.NewSession(); err != nil {
		return nil, err
	}
	modes := ssh.TerminalModes{
		ssh.ECHO:          0,     // disable echoing
		ssh.TTY_OP_ISPEED: 14400, // input speed = 14.4kbaud
		ssh.TTY_OP_OSPEED: 14400, // output speed = 14.4kbaud
	}
	if err := session.RequestPty("xterm", 80, 40, modes); err != nil {
		return nil, err
	}
	return session, nil
}

func CmdRun(username, password, host, key, cmd string, port, timeout int) (res SshResult) {
	chSSH := make(chan SshResult)
	go cmdOutputToChan(username, password, host, key, cmd, port, chSSH)
	select {
	case <-time.After(time.Duration(timeout) * time.Second):
		res.Host = host
		res.Success = false
		res.Result = fmt.Sprintf("run timeout：[%s] second.", strconv.Itoa(timeout))
		return res
	case res = <-chSSH:
		return res
	}
}

func cmdOutputToChan(username, password, host, key string, cmd string, port int, ch chan SshResult) {
	session, err := initSshConnect(username, password, host, key, port)
	var sshResult SshResult
	sshResult.Host = host
	if err != nil {
		sshResult.Success = false
		sshResult.Result = err.Error()
		ch <- sshResult
		return
	}
	defer session.Close()
	var outbt, errbt bytes.Buffer
	session.Stdout = &outbt
	session.Stderr = &errbt
	if err = session.Run(cmd); err != nil {
		sshResult.Success = false
		sshResult.Result = err.Error()
		ch <- sshResult
		return
	}
	if errbt.String() != "" {
		sshResult.Success = false
		sshResult.Result = errbt.String()
		ch <- sshResult
	} else {
		sshResult.Success = true
		sshResult.Result = outbt.String()
		ch <- sshResult
	}
	return
}
