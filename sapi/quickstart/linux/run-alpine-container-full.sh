#!/bin/bash

set -exu
__DIR__=$(
  cd "$(dirname "$0")"
  pwd
)
__PROJECT__=$(
  cd ${__DIR__}/../../../
  pwd
)
cd ${__DIR__}

{
  docker stop swoole-cli-alpine-dev
  sleep 5
} || {
  echo $?
}
cd ${__DIR__}

IMAGE=alpine:3.16

:<<'EOF'
   启动此容器

   已经内置了 php 、composer 、 编译好的依赖库


EOF

OS=$(uname -s)
ARCH=$(uname -m)

MIRROR=""
while [ $# -gt 0 ]; do
  case "$1" in
  --mirror)
    MIRROR="$2"
    shift
    ;;
  esac
  shift $(($# > 0 ? 1 : 0))
done

case $ARCH in
'x86_64')
  IMAGE=docker.io/jingjingxyk/build-swoole-cli:all-dependencies-alpine-3.17-php8-v1.0.0-x86_64-20230917T123120Z
  if [ "$MIRROR" = 'china' ] ; then
    IMAGE=registry.cn-beijing.aliyuncs.com/jingjingxyk-public/app:all-dependencies-alpine-3.17-php8-v1.0.0-x86_64-20230917T123120Z
  fi
  ;;
'aarch64')
  IMAGE=docker.io/jingjingxyk/build-swoole-cli:all-dependencies-alpine-3.17-php8-v1.0.0-aarch64-20230917T124401Z
    if [ "$MIRROR" = 'china' ] ; then
      IMAGE=registry.cn-hangzhou.aliyuncs.com/jingjingxyk-public/app:all-dependencies-alpine-3.17-php8-v1.0.0-aarch64-20230917T124401Z
    fi
  ;;
*)
  echo "此 ${ARCH} 架构的容器 容器未配置"
  exit 0
  ;;
esac



cd ${__DIR__}
docker run --rm --name swoole-cli-alpine-dev -d -v ${__PROJECT__}:/work -w /work $IMAGE tail -f /dev/null
