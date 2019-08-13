FROM redis:5.0.5-alpine
LABEL maintainer="fxlt <daqian.sun@gaea.com>"

# 设置服务根目录
ENV SERVICE_PATH ./docker/redis

# 设置时区
ENV TIMEZONE=Asia/Shanghai
RUN ln -snf /usr/share/zoneinfo/$TIMEZONE /etc/localtime && echo $TIMEZONE > /etc/timezone

COPY ${SERVICE_PATH}/etc/redis.conf /usr/local/etc/redis/redis.conf
CMD [ "redis-server", "/usr/local/etc/redis/redis.conf"]
