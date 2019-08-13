FROM php:7.3.6-fpm-stretch
LABEL maintainer="fxlt <daqian.sun@gaea.com>"

  # 设置服务根目录
ENV SERVICE_PATH ./docker/php

  # 设置时区
ENV TIMEZONE Asia/Shanghai
RUN ln -snf /usr/share/zoneinfo/$TIMEZONE /etc/localtime && echo $TIMEZONE > /etc/timezone

  # 安装 Composer
COPY $SERVICE_PATH/pkg/composer.phar /usr/local/bin/composer
  # 配置中国镜像
  # see: https://pkg.phpcomposer.com/
RUN chmod +x /usr/local/bin/composer \
&& composer config -g repo.packagist composer https://packagist.phpcomposer.com

  # 更新安装依赖包和PHP核心拓展
  #
  # –no-install-recommends 参数来避免安装非必须的文件，从而减小镜像的体积
  # libpcre3-dev 是 opcache 必须库
  # libpq-dev 是 pdo_pgsql 必须库
RUN apt-get clean all && apt-get update && apt-get install -y \
apt-utils \
libfreetype6-dev \
libjpeg62-turbo-dev \
libpng-dev \
libpq-dev \
libpcre3-dev \
libmcrypt-dev \
libzip-dev \
librabbitmq-dev \
zip \
&& docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
&& docker-php-ext-install -j$(nproc) gd \
&& docker-php-ext-configure zip --with-libzip \
&& docker-php-ext-install -j$(nproc) zip \
&& docker-php-ext-install sockets \
&& docker-php-ext-install pcntl \
&& docker-php-ext-install pdo_mysql \
&& docker-php-ext-install opcache \
&& pecl install xdebug-2.7.2 \
&& pecl install redis-4.3.0 \
&& pecl install mcrypt-1.0.2 \
&& pecl install yaf-3.0.8 \
&& pecl install amqp-1.9.4 \
&& docker-php-ext-enable xdebug redis mcrypt yaf amqp
RUN apt-get clean
  # 配置 php.ini
COPY $SERVICE_PATH/etc/php.ini /usr/local/etc/php/php.ini


