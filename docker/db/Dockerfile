FROM mariadb:latest
LABEL maintainer="fxlt <daqian.sun@gaea.com>"

# 设置服务根目录
ENV SERVICE_PATH ./docker/db
COPY ${SERVICE_PATH}/init_data_table/init.sql /docker-entrypoint-initdb.d/
# 设置时区
ENV TIMEZONE=Asia/Shanghai
RUN ln -snf /usr/share/zoneinfo/$TIMEZONE /etc/localtime && echo $TIMEZONE > /etc/timezone
