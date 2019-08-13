#!/bin/bash

export GO111MODULE=on
export CGO_ENABLED=0
export GOOS=linux
export GOARCH=amd64
VERSION=`head -n 1 VERSION`
LOCALPATH=`pwd`
DIST_DIR="dist"
CMD_DIR="cmd"
DEPLOYMENT_DIR="deployments"
TARGET_BIN_API="itomapi"
TARGET_BIN_TASK="itomtask"
CONFIG_DIR="configs"
CONFIG_NAME="itom-example.yml"

set -e

go mod tidy
mkdir -p ${DIST_DIR}
go build -o ${LOCALPATH}/${DIST_DIR}/${TARGET_BIN_API} ${LOCALPATH}/${CMD_DIR}/${TARGET_BIN_API}
go build -o ${LOCALPATH}/${DIST_DIR}/${TARGET_BIN_TASK} ${LOCALPATH}/${CMD_DIR}/${TARGET_BIN_TASK}

# doc
# ${foreach P,${DOC_PACKAGES},godoc2md ${P} > ${P}/README.md;}
#
# tag:
# git tag -f -a "${VERSION}" -m "version ${VERSION}"
# git push --tags -f
#
# docker
# build api image
#TARGET_IMAGE=""
#cp ${LOCALPATH}/${DIST_DIR}/${TARGET_BIN_API} ${LOCALPATH}/${DEPLOYMENT_DIR}/${TARGET_BIN_API}/
#cp ${LOCALPATH}/${CONFIG_DIR}/${CONFIG_NAME} ${LOCALPATH}/${DEPLOYMENT_DIR}/${TARGET_BIN_API}/
#cd ${LOCALPATH}/${DEPLOYMENT_DIR}/${TARGET_BIN_API}/
#echo building ${TARGET_IMAGE}/${TARGET_BIN_API}:${VERSION}
#docker build -t ${TARGET_IMAGE}/${TARGET_BIN_API}:latest .
#docker tag ${TARGET_IMAGE}/${TARGET_BIN_API}:latest ${TARGET_IMAGE}/${TARGET_BIN_API}:${VERSION}
#rm -f ${TARGET_BIN_API} ${CONFIG_NAME}
#
#cp ${LOCALPATH}/${DIST_DIR}/${TARGET_BIN_TASK} ${LOCALPATH}/${DEPLOYMENT_DIR}/${TARGET_BIN_TASK}/
#cp ${LOCALPATH}/${CONFIG_DIR}/${CONFIG_NAME} ${LOCALPATH}/${DEPLOYMENT_DIR}/${TARGET_BIN_TASK}/
#cd ${LOCALPATH}/${DEPLOYMENT_DIR}/${TARGET_BIN_TASK}/
#echo building ${TARGET_IMAGE}/${TARGET_BIN_TASK}:${VERSION}
#docker build -t ${TARGET_IMAGE}/${TARGET_BIN_TASK}:latest .
#docker tag ${TARGET_IMAGE}/${TARGET_BIN_TASK}:latest ${TARGET_IMAGE}/${TARGET_BIN_TASK}:${VERSION}
#rm -f ${TARGET_BIN_TASK} ${CONFIG_NAME}
#
# dockerpush:
#docker push ${TARGET_IMAGE}/${TARGET_BIN_API}:latest
#docker push ${TARGET_IMAGE}/${TARGET_BIN_API}:${VERSION}
#
#docker push ${TARGET_IMAGE}/${TARGET_BIN_TASK}:latest
#docker push ${TARGET_IMAGE}/${TARGET_BIN_TASK}:${VERSION}
#
# docker clean
#docker rmi ${TARGET_IMAGE}/${TARGET_BIN_API}:latest
#docker rmi ${TARGET_IMAGE}/${TARGET_BIN_API}:${VERSION}
#
#docker rmi ${TARGET_IMAGE}/${TARGET_BIN_TASK}:latest
#docker rmi ${TARGET_IMAGE}/${TARGET_BIN_TASK}:${VERSION}
