USE `itom`;

DROP TABLE IF EXISTS `accredit`;
CREATE TABLE `accredit` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID',
  `project_id` int(11) NOT NULL COMMENT 'ID',
  `create_user` int(11) NOT NULL,
  `create_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `accredit_user_project` (`user_id`,`project_id`),
  KEY `create_user` (`create_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户项目权限表';

DROP TABLE IF EXISTS `captcha_codes`;
CREATE TABLE `captcha_codes` (
  `id` varchar(40) NOT NULL,
  `namespace` varchar(32) NOT NULL,
  `code` varchar(32) NOT NULL,
  `code_display` varchar(32) NOT NULL,
  `created` int(11) NOT NULL,
  `audio_data` mediumblob DEFAULT NULL,
  PRIMARY KEY (`id`,`namespace`),
  KEY `created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='登录验证码记录';

DROP TABLE IF EXISTS `custom_group`;
CREATE TABLE `custom_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL COMMENT '自定义组名称',
  `description` text DEFAULT NULL COMMENT '自定义组描述',
  `project_id` int(11) NOT NULL COMMENT '所属项目ID',
  `server_id` text NOT NULL COMMENT '分组中包含的多个服务器id，JSON结构',
  `group_type` int(11) NOT NULL DEFAULT 100 COMMENT '分组类型100是服务器分组，200是容器分组',
  `create_user` int(11) NOT NULL COMMENT '创建分组的用户ID',
  `create_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp() COMMENT '系统创建时间戳',
  PRIMARY KEY (`id`),
  KEY `project_id_index` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='命令或任务的自定义分组';

DROP TABLE IF EXISTS `docker_compose`;
CREATE TABLE `docker_compose` (
  `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '编排模板id',
  `name` varchar(200) NOT NULL COMMENT '名称',
  `description` varchar(255) DEFAULT NULL COMMENT '描述',
  `project_id` bigint(10) unsigned NOT NULL COMMENT '所属项目id',
  `create_user` bigint(10) unsigned NOT NULL COMMENT '创建者id',
  `create_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '创建时间',
  `status` smallint(3) unsigned NOT NULL COMMENT '编排模板状态200正常100禁用',
  `image_name` text NOT NULL COMMENT '编排模板中关联的镜像名称（image:tag)，JSON结构，可以多个',
  `image_times` text NOT NULL COMMENT '编排模板关联的镜像启动顺序以及时间间隔 JSON结构',
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `status` (`status`),
  KEY `project__index` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='容器编排模板';

DROP TABLE IF EXISTS `docker_image`;
CREATE TABLE `docker_image` (
  `id` bigint(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `image_id` varchar(255) NOT NULL COMMENT '保存在服务器上的镜像长id',
  `short_id` varchar(20) NOT NULL COMMENT '保存在服务器上的镜像的短id',
  `ip` varchar(255) NOT NULL COMMENT '服务器的内网ip',
  `name_version` varchar(255) NOT NULL COMMENT '镜像名称，格式是name:tag\n',
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '产生记录的时间戳',
  PRIMARY KEY (`id`),
  KEY `name_version` (`name_version`),
  KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='拉取镜像到服务器上的记录';

DROP TABLE IF EXISTS `gaea_server`;
CREATE TABLE `gaea_server` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '服务器id',
  `name` varchar(120) NOT NULL COMMENT '服务器自定义名称',
  `internal_ip` varchar(30) NOT NULL COMMENT '服务器内网ip',
  `public_ip` varchar(30) NOT NULL COMMENT '服务器外网ip',
  `create_time` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '服务器导入时间',
  `include_type` smallint(3) unsigned NOT NULL DEFAULT 200 COMMENT '服务器导入类型100是导入进来未使用的200是已经使用',
  `status` smallint(3) NOT NULL COMMENT '服务器状态200正常100未使用400删除',
  `cpu` int(4) unsigned NOT NULL COMMENT '服务器cpu数量，单位个核',
  `ram` int(4) unsigned NOT NULL COMMENT '服务器内存大小，单位MB',
  `cds` int(10) unsigned NOT NULL COMMENT '服务器磁盘大小，单位GB',
  PRIMARY KEY (`id`),
  UNIQUE KEY `public_ip` (`public_ip`),
  UNIQUE KEY `internal_ip` (`internal_ip`),
  KEY `status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务器信息详情';

DROP TABLE IF EXISTS `order_info`;
CREATE TABLE `order_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '命令自增id',
  `type` int(5) NOT NULL COMMENT '命令类型100是在服务器上执行200是在容器上执行300是在itom所在服务器本地执行',
  `name` varchar(255) NOT NULL COMMENT '命令自定义名称',
  `description` varchar(255) NOT NULL COMMENT '命令自定义描述',
  `order` text NOT NULL COMMENT '命令详细内容做base64编码',
  `project_id` int(11) NOT NULL COMMENT '所属项目id',
  `update_status` smallint(6) NOT NULL COMMENT '命令是否可分享200为可以分享给别人使用但不能修改只有本人才可以修改',
  `run_status` smallint(6) NOT NULL COMMENT '执行状态200可以分享100不可分享，开源版本此字段废除，不支持别人修改，只能运行，运行判断的字段使用update_status',
  `create_user` int(10) NOT NULL COMMENT '命令创建者id',
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '命令创建时间戳',
  PRIMARY KEY (`id`),
  KEY `create__index` (`create_user`),
  KEY `project__index` (`project_id`),
  KEY `update__index` (`update_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='itom命令详情表';

DROP TABLE IF EXISTS `order_operate_log`;
CREATE TABLE `order_operate_log` (
  `id` bigint(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '操作记录自增id',
  `step_no` int(11) NOT NULL COMMENT '记录如果是任务或者异步操作有可能会有多个执行步骤，记录步骤的序号',
  `task_id` varchar(255) CHARACTER SET utf8 NOT NULL COMMENT '每次执行异步操作包括任务和命令都会分发一个全局唯一id',
  `task_type` varchar(50) NOT NULL DEFAULT 'async' COMMENT '记录操作的执行方式是同步还是异步，默认异步',
  `uid` int(11) NOT NULL COMMENT '执行者的id',
  `request` text NOT NULL COMMENT '执行操作的具体内容',
  `operate` varchar(255) NOT NULL COMMENT '执行操作的具体内容的分类类型如拉取镜像 启动镜像 执行任务等',
  `result` longtext DEFAULT NULL COMMENT '执行操作后返回的结果信息',
  `project_id` int(11) NOT NULL DEFAULT 0 COMMENT '操作所属项目id',
  `project_name` varchar(255) NOT NULL COMMENT '所属项目名称',
  `instance_id` int(11) NOT NULL DEFAULT 0 COMMENT '执行操作所属的服务器id',
  `instance_name` varchar(255) NOT NULL COMMENT '操作所属服务器名称',
  `cloud_type` varchar(100) NOT NULL COMMENT '云服务类型',
  `ip` varchar(255) NOT NULL COMMENT '所属服务器内网ip',
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '命令执行的开始时间',
  `end_at` timestamp NULL DEFAULT current_timestamp() COMMENT '命令执行完成的结束时间',
  PRIMARY KEY (`id`),
  KEY `instance_id` (`instance_id`),
  KEY `uid` (`uid`),
  KEY `project_id` (`project_id`),
  KEY `cloud_type` (`cloud_type`),
  KEY `task_id` (`task_id`),
  KEY `create__index` (`create_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='itom执行操作记录表包括命令和系统各种异步执行';

DROP TABLE IF EXISTS `project`;
CREATE TABLE `project` (
  `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '项目id',
  `name` varchar(255) NOT NULL COMMENT '项目名称',
  `project_descption` varchar(255) NOT NULL COMMENT '描述',
  `create_user` bigint(10) unsigned NOT NULL COMMENT '项目创建者id',
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'é¡¹ç›®åˆ›å»ºæ—¶é—´',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='项目详情表';

DROP TABLE IF EXISTS `project_docker`;
CREATE TABLE `project_docker` (
  `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '容器id',
  `name` varchar(255) NOT NULL COMMENT '容器名称',
  `description` varchar(255) DEFAULT NULL COMMENT '容器描述',
  `container_id` varchar(255) NOT NULL COMMENT '容器所在服务器上的container_id',
  `project_id` bigint(10) NOT NULL COMMENT '所属项目ID',
  `cloud_type` varchar(100) NOT NULL DEFAULT 'gaea' COMMENT '所属云服务类型默认gaea',
  `status` smallint(3) NOT NULL DEFAULT 200 COMMENT '容器状态200正常100关闭',
  `image_name` varchar(255) NOT NULL COMMENT '容器所使用的镜像名称 name:tag',
  `ip` varchar(255) NOT NULL COMMENT '所属服务器内网IP',
  `instance_id` bigint(10) NOT NULL COMMENT '所属服务器ID',
  `create_user` bigint(10) NOT NULL COMMENT 'ID',
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `container_id` (`container_id`),
  KEY `status` (`status`),
  KEY `instance_id` (`instance_id`,`cloud_type`),
  KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='项目所属容器详情表';

DROP TABLE IF EXISTS `project_server`;
CREATE TABLE `project_server` (
  `project_id` bigint(10) unsigned NOT NULL COMMENT '项目ID 非外键',
  `server_id` bigint(10) unsigned NOT NULL COMMENT '服务器id 非外键',
  `type` varchar(60) NOT NULL DEFAULT 'gaea' COMMENT '云服务商类型，默认gaea即普通服务器类型',
  `name` varchar(120) NOT NULL COMMENT '服务器名称同服务器详情表中的name，这里设置方便搜索查询',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '同上',
  `status` smallint(3) unsigned NOT NULL COMMENT '服务器状态100是正常未绑定到项目中，只保存在虚拟组中200正常绑定可以进行编排模板关联400是表删除，itom开源版本目前是直接删记录，不会是标记状态',
  `run_status` varchar(20) NOT NULL COMMENT '服务器运行状态一般是running  stop starting',
  `include_type` smallint(3) unsigned NOT NULL DEFAULT 100 COMMENT '导入类型100是导入进来的200是在itom上创建的 itom开源版本不存在200的',
  `group_id` bigint(10) unsigned NOT NULL COMMENT '服务器网络拓扑分组id，即server_group的id',
  `compose_id` bigint(10) unsigned NOT NULL COMMENT '关联的编排模板id',
  `template_id` bigint(10) unsigned NOT NULL COMMENT 'itom开源版本废弃字段 原版本实例模板id',
  `create_at` datetime NOT NULL,
  `create_user` bigint(10) NOT NULL COMMENT '创建者id',
  KEY `server_id` (`server_id`),
  KEY `type` (`type`),
  KEY `project_id` (`project_id`),
  KEY `status` (`status`),
  KEY `compose__index` (`compose_id`),
  KEY `group__index` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='项目与服务器关联表，与服务器详情表是事务DML';

DROP TABLE IF EXISTS `server_env`;
CREATE TABLE `server_env` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '关联自增id',
  `image_name` varchar(256) NOT NULL COMMENT '实例所属的编排模板中的镜像名称',
  `server_id` int(10) NOT NULL COMMENT '服务器ID',
  `container_name` varchar(255) NOT NULL COMMENT '容器名称',
  `container_describe` varchar(255) DEFAULT '' COMMENT '容器描述',
  `container_num` int(11) NOT NULL DEFAULT 1 COMMENT '自动数量，开源版本暂不可用，可以自建多个编排模板实现',
  PRIMARY KEY (`id`),
  UNIQUE KEY `index3` (`image_name`,`server_id`),
  KEY `idx_server_env_image_id` (`image_name`),
  KEY `idx_server_env_server_id` (`server_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务器与容器环境变量关联表，此表仅关联，详情在server_image_env，可以合并的，下个版本合并';

DROP TABLE IF EXISTS `server_group`;
CREATE TABLE `server_group` (
  `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '服务器拓扑分组自增id',
  `name` varchar(200) NOT NULL COMMENT '拓扑自定义分组名称',
  `project_id` bigint(10) unsigned NOT NULL COMMENT '所属项目id',
  `type` smallint(3) unsigned NOT NULL DEFAULT 100 COMMENT '拓扑分组类型100不可复制200可复制分组',
  `create_user` bigint(20) NOT NULL COMMENT '创建者id',
  `create_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '创建时间戳',
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `project_id` (`project_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='网络拓扑分组';

DROP TABLE IF EXISTS `server_image_env`;
CREATE TABLE `server_image_env` (
  `server_id` bigint(20) unsigned NOT NULL COMMENT '所属实例ID',
  `image_name` varchar(256) NOT NULL COMMENT '所属服务器编排模板中的image_name json中的一个',
  `key_name` varchar(255) NOT NULL COMMENT '环境变量的KEY名称',
  `key_value` text DEFAULT NULL COMMENT '环境变量KEY对应的值',
  `params_type` smallint(3) DEFAULT NULL COMMENT '100是env 200是valume 300是cmd 400是entrypoint',
  `create_user` bigint(10) unsigned NOT NULL COMMENT '创建者id',
  `create_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '创建时间',
  KEY `server_id` (`server_id`),
  KEY `index2` (`image_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务器上的镜像运行所需要的环境变量对应表';

DROP TABLE IF EXISTS `task_info`;
CREATE TABLE `task_info` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL COMMENT '名称',
  `description` text DEFAULT '' COMMENT '描述',
  `project_id` int(11) NOT NULL COMMENT '所属项目id',
  `update_status` int(5) NOT NULL DEFAULT 100 COMMENT '修改100别人不可以修改200任何人可修改',
  `run_status` int(5) NOT NULL DEFAULT 200 COMMENT '100只能自己执行200别人也可以执行',
  `create_user` int(10) NOT NULL COMMENT '创建者id',
  `create_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp() COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='任务详情表';

DROP TABLE IF EXISTS `task_order`;
CREATE TABLE `task_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL COMMENT 'task_info中的自增id即任务id，非外键',
  `order_id` int(11) NOT NULL COMMENT 'order_info中的自增id，即命令id，非外键',
  `order_sort` int(11) NOT NULL COMMENT '执行排序，即执行多组命令中的第几个步骤',
  `order_object` text NOT NULL COMMENT '执行命令的目标对象 json结构可能多个实例/容器',
  PRIMARY KEY (`id`),
  KEY `order__index` (`order_id`),
  KEY `task__index` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='任务与命令关联表';

DROP TABLE IF EXISTS `timed_task`;
CREATE TABLE `timed_task` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL COMMENT '名称',
  `description` varchar(255) NOT NULL COMMENT '描述',
  `project_id` int(11) NOT NULL COMMENT '所属项目id',
  `task_id` int(11) NOT NULL COMMENT '关联的任务id，task_info的自增id',
  `type` int(5) NOT NULL COMMENT '执行方式：100是执行一次 200是循环执行',
  `run_condition` varchar(255) NOT NULL COMMENT '执行条件即crontab中的时间方式',
  `create_user` int(11) NOT NULL COMMENT '创建者id',
  `create_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp() COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `task__index` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='定时任务表';

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT COMMENT 'ç”¨æˆ·id',
  `name` varchar(60) CHARACTER SET utf8 NOT NULL COMMENT '账号名，原则上是手机号或者中英文混写非特殊符号，系统不能自己创建账号，由root账户在后台创建，系统尽量只保证root唯一一个账号',
  `password` varchar(128) CHARACTER SET utf8 NOT NULL COMMENT '账号密码',
  `type` varchar(10) NOT NULL DEFAULT 'admin' COMMENT 'User manager type: root / admin root最高管理员，建议itom中仅包留一个，只用于hub维护 账户管理 权限管理',
  `status` smallint(3) unsigned NOT NULL COMMENT '账号状态 200正常 400异常',
  `reg_time` timestamp NULL DEFAULT current_timestamp() COMMENT '创建成功的时间戳',
  `login_time` timestamp NULL DEFAULT current_timestamp() COMMENT '上次登录成功的时间戳',
  `login_ip` int(10) unsigned DEFAULT NULL COMMENT '上次登录成功的ip',
  `login_err` tinyint(1) unsigned DEFAULT NULL COMMENT '记录登录错误的次数超过5次系统锁定无法登录24小时后解除或者root账户解除',
  `login_err_at` timestamp NULL DEFAULT current_timestamp() COMMENT '登录错误的时间戳',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_2` (`name`),
  KEY `name` (`name`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='itom用户表';

LOCK TABLES `user` WRITE;
INSERT INTO `itom`.`user` (`name`, `password`, `type`, `status`) VALUES ('system', '$2y$10$EGFnEYU/iQFMp8Qcw3Cr.eWOX.pDAA4bsoDFzrq6owc057brObDPy', 'root', 200);
UNLOCK TABLES;

DROP TABLE IF EXISTS `var_info`;
CREATE TABLE `var_info` (
  `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `var_name` varchar(255) NOT NULL,
  `var_type` varchar(30) NOT NULL DEFAULT 'String',
  `type` smallint(3) unsigned NOT NULL,
  `var_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `project_id` bigint(10) unsigned DEFAULT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `create_user` bigint(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='全局变量信息表，开源版废弃，不能删除相关代码存在';

DROP TABLE IF EXISTS `var_quote`;
CREATE TABLE `var_quote` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `var_name` varchar(255) NOT NULL,
  `type` smallint(4) NOT NULL,
  `quote_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='全局变量函数表，开源版本废弃，不能删除相关代码存在';
