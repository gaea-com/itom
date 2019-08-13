FROM nginx:1.17.2-alpine
LABEL maintainer="fxlt <daqian.sun@gaea.com>"

ENV PROXY_HOST 127.0.0.1

# 设置时区
ENV TIMEZONE=Asia/Shanghai
RUN ln -snf /usr/share/zoneinfo/$TIMEZONE /etc/localtime && echo $TIMEZONE > /etc/timezone
