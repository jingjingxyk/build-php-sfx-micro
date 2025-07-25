#!/usr/bin/env bash

set -exu
__DIR__=$(
  cd "$(dirname "$0")"
  pwd
)
__PROJECT__=$(
  cd ${__DIR__}/../../../
  pwd
)
cd ${__PROJECT__}

# cp -f /cygdrive/c/setup-x86_64.exe  /cygdrive/c/cygwin/bin/setup-x86_64.exe
# cp -f /cygdrive/c/setup.exe  /cygdrive/c/cygwin/bin/setup-x86_64.exe

# download cygwin
# wget https://cygwin.com/setup-x86_64.exe

# cygwin 移动到 bin 目录
# mv setup-x86_64.exe C:/cygwin64/bin/setup-x86_64.exe

## 设置 站点镜像 地址 为
##  http://mirrors.ustc.edu.cn/cygwin/
##  或者
##  https://mirrors.tuna.tsinghua.edu.cn/cygwin/
## 多个包之间，用逗号分隔

SITE='https://mirrors.kernel.org/sourceware/cygwin/'
while [ $# -gt 0 ]; do
  case "$1" in
  --mirror)
    if [ "$2" = 'china' ]; then
      SITE='https://mirrors.ustc.edu.cn/cygwin/'
    fi
    ;;
  --*)
    echo "Illegal option $1"
    ;;
  esac
  shift $(($# > 0 ? 1 : 0))
done

# setup-x86_64.exe  --no-desktop --no-shortcuts --no-startmenu --quiet-mode --disable-buggy-antivirus    --site  http://mirrors.ustc.edu.cn/cygwin/ --packages libzstd-devel

setup-x86_64.exe --quiet-mode --disable-buggy-antivirus --site $SITE

## 多个包之间，用逗号分隔

PACKAGES="make,git,curl,wget,tar,libtool,bison,gcc-g++,autoconf,automake"
PACKAGES="${PACKAGES},cmake,openssl,binutils"
PACKAGES="${PACKAGES},libssl-devel,libcurl-devel,libxml2-devel,libxslt-devel"
PACKAGES="${PACKAGES},libssh2-devel,libidn2-devel"
PACKAGES="${PACKAGES},libgmp-devel,libsqlite3-devel,libpcre-devel,libpcre2-devel"
PACKAGES="${PACKAGES},libMagick-devel,ImageMagick,libpng-devel,libjpeg-devel,libfreetype-devel,libwebp-devel"
PACKAGES="${PACKAGES},zlib-devel,libbz2-devel,liblz4-devel,liblzma-devel,libzip-devel"
PACKAGES="${PACKAGES},libzstd-devel,libbrotli-devel"
PACKAGES="${PACKAGES},zip,unzip,xz"
PACKAGES="${PACKAGES},libreadline-devel,libicu-devel,libonig-devel,libcares-devel,libsodium-devel,libyaml-devel"
PACKAGES="${PACKAGES},libintl-devel,gettext-devel"
PACKAGES="${PACKAGES},libpq5,libpq-devel"
PACKAGES="${PACKAGES},flex"
PACKAGES="${PACKAGES},cygwin-devel,libnet6-devel"
PACKAGES="${PACKAGES},libwrap-devel"
PACKAGES="${PACKAGES},libedit-devel"

setup-x86_64.exe --no-desktop --no-shortcuts --no-startmenu --quiet-mode --disable-buggy-antivirus --site $SITE --packages $PACKAGES
