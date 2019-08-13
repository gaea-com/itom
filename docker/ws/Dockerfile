FROM php:7.3.6-cli-stretch
LABEL maintainer="fxlt <daqian.sun@gaea.com>"
# 设置服务根目录
ENV SERVICE_PATH ./docker/ws
 # 设置时区
ENV TIMEZONE Asia/Shanghai
RUN ln -snf /usr/share/zoneinfo/$TIMEZONE /etc/localtime && echo $TIMEZONE > /etc/timezone

RUN apt-get clean all && apt-get update && apt-get install -y \
apt-utils \
libmcrypt-dev \
&& docker-php-ext-install opcache \
&& pecl install redis-4.3.0 \
&& pecl install swoole-4.3.4 \
&& pecl install mcrypt-1.0.2 \
&& docker-php-ext-enable redis swoole mcrypt
# 配置 wsserver 服务脚本
ADD $SERVICE_PATH/websocket.php /app/websocket.php
WORKDIR /app
