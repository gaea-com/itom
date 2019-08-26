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
  
&nbsp;&nbsp;&nbsp;&nbsp;ubuntu 18.04/macOS Mojave 10.14.6  

&nbsp;&nbsp;&nbsp;&nbsp;docker-ce version 19.03+  (确认docker engine server和client均是此版本)  

&nbsp;&nbsp;&nbsp;&nbsp;docker-compose 1.24.1  
            
&nbsp;&nbsp;&nbsp;&nbsp;以上环境已测试，docker-compose低于此版本有可能导致yml文件格式不支持。

### 方式一:docker compose
   
如果ubuntu自带docker版本较低，请根据此链接进行更新：

<https://docs.docker.com/install/linux/docker-ce/ubuntu/>
   
docker-compose更新:
   
```text
curl -L https://github.com/docker/compose/releases/download/1.24.1/docker-compose-`uname -s`-`uname -m` -o /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose
```
   
如果不能直通外网，可以使用itom已经build好的镜像，全部下载后启动即可，镜像保存在阿里云镜像仓库。

请将项目根目录下的docker-compose.yml文件中的镜像进行修改

service web的image：<https://registry.cn-hangzhou.aliyuncs.com/gaea-com/itom:web_v1.0>

service php的image：<https://registry.cn-hangzhou.aliyuncs.com/gaea-com/itom:php_v1.0>

service rabbitmq的image：<https://registry.cn-hangzhou.aliyuncs.com/gaea-com/itom:mq_v1.0>

service db的image：<https://registry.cn-hangzhou.aliyuncs.com/gaea-com/itom:db_v1.0>

service console的image：<https://registry.cn-hangzhou.aliyuncs.com/gaea-com/itom:console_v1.0>

service wsserver的image：<https://registry.cn-hangzhou.aliyuncs.com/gaea-com/itom:ws_v1.0>

service redis的image：<https://registry.cn-hangzhou.aliyuncs.com/gaea-com/itom:redis_v1.0>

service python-itomtask的image：<https://registry.cn-hangzhou.aliyuncs.com/gaea-com/itom:py-task_v1.0>

service go-itomapi的image：<https://registry.cn-hangzhou.aliyuncs.com/gaea-com/itom:go-api_v1.0>

service go-itomtask的image：<https://registry.cn-hangzhou.aliyuncs.com/gaea-com/itom:go-task_v1.0>

启动容器：在itom根目录下执行 docker-compose up -d
   
**运行前请检查app/upload和app/script/log目录以及可读写权限**

   
##  系统相关文档
  使用教程:[视频地址](https://bilibili.com/video/xxxxxx)
   
##  License 
  GPLv3



