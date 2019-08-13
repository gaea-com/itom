FROM rabbitmq:3.7.15-management-alpine
LABEL maintainer="fxlt <daqian.sun@gaea.com>"

# 设置服务根目录
#ENV SERVICE_PATH ./docker/mq

# 设置时区
ENV TIMEZONE=Asia/Shanghai
RUN ln -snf /usr/share/zoneinfo/$TIMEZONE /etc/localtime && echo $TIMEZONE > /etc/timezone

#COPY ${SERVICE_PATH}/etc/redis.conf /usr/local/etc/redis/redis.conf
CMD ["rabbitmq-server" , "start"]
