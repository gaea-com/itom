FROM golang:stretch as BIN
# 使用 goproxy 代理，国内的网络，你懂得
ENV GOPROXY=https://goproxy.cn
ENV CGO_ENABLED=0
ENV GOOS=linux
ENV GOARCH=amd64
ENV SERVICE_PATH ./go-itom-api
COPY $SERVICE_PATH/ /app
WORKDIR /app
RUN go mod tidy && \
    mkdir -p /app/dist && \
    go build -a -ldflags '-extldflags "-static"' -v -o /app/dist/itomapi /app/cmd/itomapi/

FROM alpine:latest
# 拷贝编译好的二进制文件
COPY --from=BIN /app/dist/itomapi /app/itomapi
ENV SERVICE_PATH ./go-itom-api
# 拷贝配置文件
COPY $SERVICE_PATH/configs/itom-example.yml /app/config.yml
WORKDIR /app
# 默认监听 8000 端口，端口配置请修改 configs/itom-example.yml 中的 system.port 选项
EXPOSE 8000
# 启动命令
CMD ["/app/itomapi", "-config", "/app/config.yml"]