<p align="center">
    <img src="python-itom-task/assets/itom_logo.png" width="500" hegiht="313" align=center>
</p>

# 系统说明
itom 是一套基于容器的运维管理系统，面向由服务器初步向容器迁移过渡的个人或中小型企业，适用于对容器的简单部署、运行和管理。

可以满足以下需要：  
- 自动拉取公共镜像仓库或私有仓库的镜像到本地服务器
     
- 易用的编排模板和环境变量设置、宿主机管理和容器管理
    
- 可以向宿主机和容器以分组的方式执行命令、任务、定时任务
       
## 快速部署

环境需求：  
ubuntu 18.04/macOS Mojave 10.14.6

docker-ce version 19.03+

docker-compose 1.24.1
            
以上环境已测试
            
### 方式一:docker compose
   
如果ubuntu自带docker版本较低，请根据此链接进行更新：

<https://docs.docker.com/install/linux/docker-ce/ubuntu/>
   
docker-compose更新:
   
```text
curl -L https://github.com/docker/compose/releases/download/1.24.1/docker-compose-`uname -s`-`uname -m` -o /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose
```
   
如果不能直通外网，可以使用itom已经build好的镜像，全部下载后启动即可。

阿里云镜像仓库地址：<https://registry.cn-beijing.aliyuncs.com/gaea-com/itom> (待上传)
   
启动容器：

在itom根目录下执行：

docker-compose up -d
  
### 方式二:常规服务器部署
   
**运行前请检查app/upload和app/script/log目录以及可读写权限**
   
##  系统相关文档
  使用教程:[视频地址](https://bilibili.com/video/xxxxxx)
  
  请查看doc目录(待补充)  
   
##  License 
  GPLv3



