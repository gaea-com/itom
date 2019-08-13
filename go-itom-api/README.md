<p align="center">
    <img src="assets/itom_logo.png" width="500" hegiht="313" align=center>
</p>

# ITOM-docker

ITOM 是一款强大的 docker 管理系统，用于帮助您管理自己的 docker 私有云，并轻松部署和管理您的容器服务。

----

## 开始使用 ITOM

### 代码构建

go1.11 或以上

`bash Makefile.sh`

生成的程序在 dist 目录下 (itomapi 和 itomtask)

### 程序功能说明

#### itomapi

- http 服务器，用于接收各类 docker 请求
- docker 批量操作(包含 拉取镜像、启动容器、销毁容器、销毁镜像 等等 docker 常用的操作)
- webshell 功能(进入指定的容器中进行相关操作)
- 指定容器内的文件下载、文件上传(仅可针对正在运行的容器操作)

配置说明： 详情参见 configs/itom-example.yml

#### itomtask

- 监听在 redis 上的服务，用于等待各类批量 docker 相关操作任务

配置说明： 详情参见 configs/itom-example.yml

## 使用须知

首先要保证在您的 docker 私有云中，各个 docker 务必开放端口(默认是关闭的)，

因为 itom 系统是通过网络进行 docker 的管理，一个配置样例如下：

ubuntu 系统中

需要修改 /lib/systemd/system/docker.service 文件

在参数 ExecStart= 增加如下启动配置

`-H tcp://0.0.0.0:2375`

修改后的配置类似这样

```
 [Service]
 Type=notify
 # the default is not to use systemd for cgroups because the delegate issues still
 # exists and systemd currently does not support the cgroup feature set required
 # for containers run by docker
 ExecStart=/usr/bin/dockerd -H fd:// -H tcp://0.0.0.0:2375
```

之后生效这个配置

```
systemctl daemon-reload
systemctl enable docker
systemctl restart docker
```

