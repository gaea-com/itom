FROM php:7.3.6-cli-stretch
LABEL maintainer="fxlt <daqian.sun@gaea.com>"
# 设置服务根目录
ENV SERVICE_PATH ./docker/console
 # 设置时区
ENV TIMEZONE Asia/Shanghai
RUN ln -snf /usr/share/zoneinfo/$TIMEZONE /etc/localtime && echo $TIMEZONE > /etc/timezone

RUN apt-get clean all && apt-get update && apt-get install -y \
apt-utils \
librabbitmq-dev \
procps \
gawk \
cron \
&& docker-php-ext-install opcache \
&& docker-php-ext-install pdo_mysql \
&& pecl install redis-4.3.0 \
&& pecl install amqp-1.9.4 \
&& pecl install yaf-3.0.8 \
&& pecl install swoole-4.3.4 \
&& docker-php-ext-enable redis amqp yaf swoole
RUN apt-get clean

COPY $SERVICE_PATH/itom_cron /data/itom_cron
COPY $SERVICE_PATH/php.ini /usr/local/etc/php/php.ini
COPY $SERVICE_PATH/itom_sh.sh /data/itom_sh.sh
RUN chmod +x /data/itom_sh.sh
WORKDIR /data
ENTRYPOINT ["/bin/bash", "/data/itom_sh.sh"]