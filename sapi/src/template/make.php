<?php
/**
 * @var $this SwooleCli\Preprocessor
 */

use SwooleCli\Library;
use SwooleCli\Preprocessor;

?>
#!/usr/bin/env bash
<?php if (in_array($this->buildType, ['dev','debug'])) : ?>
set -x
<?php  endif; ?>
SRC=<?= $this->phpSrcDir . PHP_EOL ?>
ROOT=<?= $this->getRootDir() . PHP_EOL ?>
PREPARE_ARGS="<?= implode(' ', $this->getPrepareArgs())?>"
export LOGICAL_PROCESSORS=<?= trim($this->logicalProcessors). PHP_EOL ?>
export CMAKE_BUILD_PARALLEL_LEVEL=<?= $this->maxJob. PHP_EOL ?>

export CC=<?= $this->cCompiler . PHP_EOL ?>
export CXX=<?= $this->cppCompiler . PHP_EOL ?>
export LD=<?= $this->lld . PHP_EOL ?>
export PKG_CONFIG_PATH=<?= implode(':', $this->pkgConfigPaths) . PHP_EOL ?>
export PATH=<?= implode(':', $this->binPaths) . PHP_EOL ?>
OPTIONS="--disable-all \
--disable-cgi  \
--enable-shared=no \
--enable-static=yes \
--without-valgrind \
--enable-cli  \
--disable-phpdbg \
<?php foreach ($this->extensionList as $item) : ?>
    <?=$item->options?> \
<?php endforeach; ?>
<?=$this->extraOptions?>
"
set +x
<?php foreach ($this->libraryList as $item) : ?>
make_<?=$item->name?>() {
    echo "build <?=$item->name?>"

    <?php if ($item->enableBuildLibraryCached) : ?>
        <?php if ($this->installLibraryCached) :?>
        if [ -f <?= $this->getGlobalPrefix() . '/'.  $item->name ?>/.completed ] ;then
            echo "[<?=$item->name?>]  library cached , skip.."
            return 0
        fi
        if [ -f <?=$this->getBuildDir()?>/<?=$item->name?>/.completed ]; then
            rm -rf <?=$this->getBuildDir()?>/<?=$item->name?>/
        fi
        <?php endif;?>
        if [ -f <?=$this->getBuildDir()?>/<?=$item->name?>/.completed ]; then
            echo "[<?=$item->name?>] compiled, skip.."
            cd <?= $this->workDir ?>/
            return 0
        fi
    <?php else : ?>
        if [ -d <?=$this->getBuildDir()?>/<?=$item->name?>/ ]; then
        rm -rf <?=$this->getBuildDir()?>/<?=$item->name?>/
        fi
    <?php endif; ?>

    <?php if ($item->cleanBuildDirectory) : ?>
     # If the build directory exist, clean the build directory
     test -d <?=$this->getBuildDir()?>/<?=$item->name?> && rm -rf <?=$this->getBuildDir()?>/<?=$item->name?> ;
    <?php endif; ?>

    # If the source code directory does not exist, create a directory and decompress the source code archive
    if [ ! -d <?= $this->getBuildDir() ?>/<?= $item->name ?> ]; then
        mkdir -p <?= $this->getBuildDir() ?>/<?= $item->name . PHP_EOL ?>
        tar --strip-components=1 -C <?= $this->getBuildDir() ?>/<?= $item->name ?> -xf <?= $this->workDir ?>/pool/lib/<?= $item->file . PHP_EOL ?>
        result_code=$?
        if [ $result_code -ne 0 ]; then
            echo "[<?=$item->name?>] [configure FAILURE]"
            rm -rf <?=$this->getBuildDir()?>/<?=$item->name?>/
            exit  $result_code
        fi
    fi



    <?php if ($item->cleanPreInstallDirectory) : ?>
    # If the install directory exist, clean the install directory
    test -d <?=$item->preInstallDirectory?>/ && rm -rf <?=$item->preInstallDirectory?>/ ;
    <?php endif; ?>

    cd <?=$this->getBuildDir()?>/<?=$item->name?>/

    <?php if ($item->enableBuildLibraryHttpProxy) : ?>
        <?= $this->getProxyConfig() . PHP_EOL ?>
        <?php if ($item->enableBuildLibraryGitProxy) :?>
            <?= $this->getGitProxyConfig() . PHP_EOL ?>
        <?php endif;?>
    <?php endif;?>

    # use build script replace  configure、make、make install
    <?php if (empty($item->buildScript)) : ?>
    # configure
        <?php if (!empty($item->configure)) : ?>
cat <<'__EOF__'
            <?= $item->configure . PHP_EOL ?>
__EOF__
            <?=$item->configure . PHP_EOL ?>
    result_code=$?
    [[ $result_code -ne 0 ]] &&  echo "[<?=$item->name?>] [configure FAILURE]" && exit  $result_code;
        <?php endif; ?>

    # make
    make -j <?= $this->maxJob ?> <?= $item->makeOptions . PHP_EOL ?>
    result_code=$?
    [[ $result_code -ne 0 ]] &&  echo "[<?=$item->name?>] [make FAILURE]" && exit  $result_code;

    # before make install
        <?php if ($item->beforeInstallScript) : ?>
            <?=$item->beforeInstallScript . PHP_EOL ?>
    result_code=$?
    [[ $result_code -ne 0 ]] &&  echo "[<?=$item->name?>] [ before make install script FAILURE]" && exit  $result_code;
        <?php endif; ?>

    # make install
        <?php if ($item->makeInstallCommand) : ?>
    make <?= $item->makeInstallCommand ?> <?= $item->makeInstallOptions ?> <?= PHP_EOL ?>
    result_code=$?
    [[ $result_code -ne 0 ]] &&  echo "[<?=$item->name?>] [make install FAILURE]" && exit  $result_code;
        <?php endif; ?>
    <?php else : ?>
    cat <<'__EOF__'
        <?= $item->buildScript . PHP_EOL ?>
__EOF__
        <?= $item->buildScript . PHP_EOL ?>
    result_code=$?
    [[ $result_code -ne 0 ]] &&  echo "[<?=$item->name?>] [build script FAILURE]" && exit  $result_code;
    <?php endif; ?>

    # after make install
    <?php if ($item->afterInstallScript) : ?>
        <?=$item->afterInstallScript . PHP_EOL ?>
    result_code=$?
    [[ $result_code -ne 0 ]] &&  echo "[<?=$item->name?>] [ after make  install script FAILURE]" && exit  $result_code;
    <?php endif; ?>

    # build end
    <?php if ($item->enableBuildLibraryHttpProxy) :?>
        unset HTTP_PROXY
        unset HTTPS_PROXY
        unset NO_PROXY
        <?php if ($item->enableBuildLibraryGitProxy) :?>
        unset GIT_PROXY_COMMAND
        <?php endif;?>
    <?php endif;?>

    <?php if ($item->enableBuildLibraryCached) : ?>
    touch <?=$this->getBuildDir()?>/<?=$item->name?>/.completed
        <?php if ($this->installLibraryCached) :?>
    if [ -d <?= $this->getGlobalPrefix() . '/'.  $item->name ?>/ ] ;then
         touch <?= $this->getGlobalPrefix() . '/'.  $item->name ?>/.completed
    fi
        <?php endif;?>
    <?php endif; ?>

    cd <?= $this->workDir . PHP_EOL ?>
    return 0
}

clean_<?=$item->name?>() {
    cd <?=$this->getBuildDir()?> && echo "clean <?=$item->name?>"
    cd <?=$this->getBuildDir()?>/<?= $item->name ?> && make clean
    rm -f <?=$this->getBuildDir()?>/<?=$item->name?>/.completed
    if [ -f <?=$this->getGlobalPrefix()?>/<?=$item->name?>/.completed ] ;then
        rm -f <?=$this->getGlobalPrefix()?>/<?=$item->name?>/.completed
    fi
    cd <?= $this->workDir . PHP_EOL ?>
}

clean_<?=$item->name?>_cached() {
    echo "clean <?=$item->name?> [cached]"
    rm <?=$this->getBuildDir()?>/<?=$item->name?>/.completed
    if [ -f <?=$this->getGlobalPrefix()?>/<?=$item->name?>/.completed ] ;then
        rm -f <?=$this->getGlobalPrefix()?>/<?=$item->name?>/.completed
    fi
}

    <?php echo str_repeat(PHP_EOL, 1);?>
<?php endforeach; ?>

make_all_library() {
<?php foreach ($this->libraryList as $item) : ?>
    make_<?=$item->name?> && echo "[SUCCESS] make <?=$item->name?>"
<?php endforeach; ?>
    return 0
}

make_ext() {
    cd <?= $this->getPhpSrcDir() . PHP_EOL ?>
    PHP_SRC_DIR=<?= $this->getPhpSrcDir() . PHP_EOL ?>
    EXT_DIR=$PHP_SRC_DIR/ext/
    TMP_EXT_DIR=$PHP_SRC_DIR/php-tmp-ext-dir/
    mkdir -p $TMP_EXT_DIR
<?php
if ($this->buildType == 'dev') {
    echo <<<EOF

    test -d \$TMP_EXT_DIR && rm -rf \$TMP_EXT_DIR
    mkdir -p \$TMP_EXT_DIR
    cd \$EXT_DIR

    cp -rf date \$TMP_EXT_DIR
    test -d hash && cp -rf hash \$TMP_EXT_DIR
    test -d json && cp -rf json \$TMP_EXT_DIR
    cp -rf pcre \$TMP_EXT_DIR
    test -d random && cp -rf random \$TMP_EXT_DIR
    cp -rf reflection \$TMP_EXT_DIR
    cp -rf session \$TMP_EXT_DIR
    cp -rf spl \$TMP_EXT_DIR
    cp -rf standard \$TMP_EXT_DIR
    cp -rf date \$TMP_EXT_DIR
    cp -rf phar \$TMP_EXT_DIR

EOF;
}

foreach ($this->extensionMap as $extension) {
    $name = $extension->name;
    if ($extension->aliasName) {
        $name = $extension->aliasName;
    }
    if (!empty($extension->peclVersion) || $extension->enableDownloadScript || !empty($extension->url)) {
        echo <<<EOF
    cp -rf {$this->getRootDir()}/ext/{$name} \$TMP_EXT_DIR
EOF;
        echo PHP_EOL;
    } else {
        if ($this->buildType == 'dev') {
            echo <<<EOF
    cp -rf \$EXT_DIR/{$name} \$TMP_EXT_DIR
EOF;
            echo PHP_EOL;
        }
    }
}
if ($this->buildType == 'dev') {
    echo <<<EOF
    mv \$EXT_DIR/ \$PHP_SRC_DIR/del-ext/
    mv \$TMP_EXT_DIR \$PHP_SRC_DIR/ext/
EOF;
    echo PHP_EOL;
} else {
    echo <<<EOF
    cp -rf \$TMP_EXT_DIR/* \$PHP_SRC_DIR/ext/
EOF;
}
    echo PHP_EOL;
?>
    cd <?= $this->getPhpSrcDir() ?>/
    return 0
}

make_ext_hook() {
    cd <?= $this->getPhpSrcDir() ?>/
<?php foreach ($this->extHooks as $name => $value) : ?>
    # ext <?= $name ?> hook
    <?= $value($this) . PHP_EOL ?>
<?php endforeach; ?>
    cd <?= $this->getPhpSrcDir() ?>/
    return 0
}

export_variables() {
    # -all-static | -static | -static-libtool-libs
    CPPFLAGS=""
    CFLAGS=""
<?php if ($this->cCompiler=='clang') : ?>
    LDFLAGS="-static"
<?php else :?>
    LDFLAGS="-static-libgcc -static-libstdc++"
<?php endif ;?>

    LDFLAGS=""
    LIBS=""
<?php foreach ($this->variables as $name => $value) : ?>
    <?= key($value) ?>="<?= current($value) ?>"
<?php endforeach; ?>
    result_code=$?
    [[ $result_code -ne 0 ]] &&  echo " [ export_variables  FAILURE ]" && exit  $result_code;
<?php foreach ($this->exportVariables as $value) : ?>
    export  <?= key($value) ?>="<?= current($value) ?>"
<?php endforeach; ?>
    result_code=$?
    [[ $result_code -ne 0 ]] &&  echo " [ export_variables  FAILURE ]" && exit  $result_code;
    return 0
}


make_config() {
    make_php_src
    make_ext
    make_ext_hook

    cd <?= $this->phpSrcDir . PHP_EOL ?>
<?php if ($this->getInputOption('with-swoole-cli-sfx')) : ?>
    PHP_VERSION=$(cat main/php_version.h | grep 'PHP_VERSION_ID' | grep -E -o "[0-9]+")
    if [[ $PHP_VERSION -lt 80000 ]] ; then
        echo "only support PHP >= 8.0 "
    else
        # 请把这个做成 patch  https://github.com/swoole/swoole-cli/pull/55/files

    fi
<?php endif ;?>
    PHP_VERSION=$(cat main/php_version.h | grep 'PHP_VERSION_ID' | grep -E -o "[0-9]+")
    if [[ $PHP_VERSION -lt 80000 ]] ; then
        echo "only support PHP >= 8.0 "
    else
        make_php_patch_sfx_micro
        cd <?= $this->phpSrcDir . PHP_EOL ?>
        if [[ ! -f php-sfx-micro.cached ]] ;then
            cp -rf <?= $this->buildDir ?>/php_patch_sfx_micro/ sapi/micro
            patch -p1 < sapi/micro/patches/phar.patch
            touch php-sfx-micro.cached
            echo "php-sfx-micro patch ok "
        fi

        OPTIONS="${OPTIONS} --enable-micro=all-static "
    fi
    echo $OPTIONS

    test -f ./configure &&  rm ./configure
    ./buildconf --force

    ./configure --help
     export_variables
     echo $LDFLAGS > <?= $this->getWorkDir() ?>/ldflags.log
     echo $CPPFLAGS > <?= $this->getWorkDir() ?>/cppflags.log
     echo $LIBS > <?= $this->getWorkDir() ?>/libs.log
<?php if ($this->osType == 'macos') : ?>
    <?php if (isset($this->libraryMap['pgsql'])) : ?>
    sed -i.backup "s/ac_cv_func_explicit_bzero\" = xyes/ac_cv_func_explicit_bzero\" = x_fake_yes/" ./configure
    <?php endif;?>
<?php endif; ?>

   ./configure --help
    export_variables
    echo $LDFLAGS > <?= $this->getRootDir() ?>/ldflags.log
    echo $CPPFLAGS > <?= $this->getRootDir() ?>/cppflags.log
    echo $LIBS > <?= $this->getRootDir() ?>/libs.log

    ./configure $OPTIONS

    # more info https://stackoverflow.com/questions/19456518/error-when-using-sed-with-find-command-on-os-x-invalid-command-code
<?php if ($this->getOsType()=='linux') : ?>
    sed -i.backup 's/-export-dynamic/-all-static/g' Makefile
<?php endif ; ?>

}

make_build() {
    cd <?= $this->phpSrcDir . PHP_EOL ?>
    export_variables
    <?php if ($this->getOsType()=='linux') : ?>
    export LDFLAGS="$LDFLAGS  -static -all-static "
    <?php endif ;?>
    export LDFLAGS="$LDFLAGS   <?= $this->extraLdflags ?>"
    export EXTRA_CFLAGS='<?= $this->extraCflags ?>'
    make -j <?= $this->maxJob ?> micro;

<?php if ($this->osType == 'macos') : ?>
    otool -L <?= $this->phpSrcDir ?>/sapi/micro/micro.sfx
<?php else : ?>
    file <?= $this->phpSrcDir ?>/sapi/micro/micro.sfx
    readelf -h <?= $this->phpSrcDir ?>/sapi/micro/micro.sfx
<?php endif; ?>

    mkdir -p <?= BUILD_PHP_INSTALL_PREFIX ?>/bin/
    cp -f <?= $this->phpSrcDir ?>/sapi/micro/micro.sfx <?= BUILD_PHP_INSTALL_PREFIX ?>/bin/

   # elfedit --output-osabi linux sapi/cli/php
}

make_clean() {
    set -ex
    find . -name \*.gcno -o -name \*.gcda | grep -v "^\./thirdparty" | xargs rm -f
    find . -name \*.lo -o -name \*.o -o -name \*.dep | grep -v "^\./thirdparty" | xargs rm -f
    find . -name \*.la -o -name \*.a | grep -v "^\./thirdparty" | xargs rm -f
    find . -name \*.so | grep -v "^\./thirdparty" | xargs rm -f
    find . -name .libs -a -type d | grep -v "^./thirdparty" | xargs rm -rf
    rm -f libphp.la bin/swoole-cli     modules/* libs/*
    rm -f ext/opcache/jit/zend_jit_x86.c
    rm -f ext/opcache/jit/zend_jit_arm64.c
    rm -f ext/opcache/minilua
    rm -f libs.log ldflags.log cppflags.log
}

show_export_var() {
    set -x
    export_variables
}
show_lib_pkg() {
    set +x
<?php foreach ($this->libraryList as $item) : ?>
    <?php if (!empty($item->pkgNames)) : ?>
        echo -e "[<?= $item->name ?>] pkg-config : \n<?= implode(' ', $item->pkgNames) ?>" ;
    <?php else :?>
        echo -e "[<?= $item->name ?>] pkg-config : \n"
    <?php endif ?>
    echo "==========================================================="
<?php endforeach; ?>
    exit 0
}

show_lib_dep_pkg() {
    set +x
    declare -A array_name
<?php foreach ($this->libraryList as $item) :?>
    <?php
    $pkgs=[];
    $this->getLibraryDependenciesByName($item->name, $pkgs);
    $res=implode(' ', $pkgs);
    ?>
    array_name[<?= $item->name ?>]="<?= $res?>"
<?php endforeach ;?>
    if test -n  "$1"  ;then
      echo -e "[$1] dependent pkgs :\n\n${array_name[$1]} \n"
    else
      for i in ${!array_name[@]}
      do
            echo -e "[${i}] dependent pkgs :\n\n${array_name[$i]} \n"
            echo "=================================================="
      done
    fi
    exit 0
}

help() {
    set +x
    echo "./make.sh docker-build"
    echo "./make.sh docker-bash"
    echo "./make.sh docker-commit"
    echo "./make.sh docker-push"
    echo "./make.sh config"
    echo "./make.sh build"
    echo "./make.sh test"
    echo "./make.sh archive"
    echo "./make.sh archive-sfx-micro"
    echo "./make.sh all-library"
    echo "./make.sh list-library"
    echo "./make.sh list-extension"
    echo "./make.sh clean-all-library"
    echo "./make.sh clean-all-library-cached"
    echo "./make.sh sync"
    echo "./make.sh pkg-check"
    echo "./make.sh show-lib-pkg"
    echo "./make.sh show-lib-dep-pkg"
    echo "./make.sh show-export-var"
    echo "./make.sh list-swoole-branch"
    echo "./make.sh switch-swoole-branch"
    echo "./make.sh [library-name]"
    echo  "./make.sh clean-[library-name]"
    echo  "./make.sh clean-[library-name]-cached"
    echo  "./make.sh clean"
}

if [ "$1" = "docker-build" ] ;then
    cd <?=$this->getRootDir()?>/sapi/docker
    docker build -t <?= Preprocessor::IMAGE_NAME ?>:<?= $this->getBaseImageTag() ?> -f <?= $this->getBaseImageDockerFile() ?>  .
    exit 0
elif [ "$1" = "docker-bash" ] ;then
    container=$(docker ps -a -f name=<?= Preprocessor::CONTAINER_NAME ?> | tail -n +2 2> /dev/null)
    base_image=$(docker images <?= Preprocessor::IMAGE_NAME ?>:<?= $this->getBaseImageTag() ?> | tail -n +2 2> /dev/null)
    image=$(docker images <?= Preprocessor::IMAGE_NAME ?>:<?= $this->getImageTag() ?> | tail -n +2 2> /dev/null)

    if [[ -z ${container} ]] ;then
        if [[ ! -z ${image} ]] ;then
            echo "swoole-cli-builder container does not exist, try to create with image[<?= Preprocessor::IMAGE_NAME ?>:<?= $this->getImageTag() ?>]"
            docker run -it --name <?= Preprocessor::CONTAINER_NAME ?> -v ${ROOT}:<?=$this->getWorkDir()?> <?= Preprocessor::IMAGE_NAME ?>:<?= $this->getImageTag() ?> /bin/bash
        elif [[ ! -z ${base_image} ]] ;then
            echo "swoole-cli-builder container does not exist, try to create with image[<?= Preprocessor::IMAGE_NAME ?>:<?= $this->getBaseImageTag() ?>]"
            docker run -it --name <?= Preprocessor::CONTAINER_NAME ?> -v ${ROOT}:<?=$this->getWorkDir()?> <?= Preprocessor::IMAGE_NAME ?>:<?= $this->getBaseImageTag() ?> /bin/bash
        else
            echo "<?= Preprocessor::IMAGE_NAME ?>:<?= $this->getImageTag() ?> image does not exist, try to pull"
            echo "create container with <?= Preprocessor::IMAGE_NAME ?>:<?= $this->getImageTag() ?> image"
            docker run -it --name <?= Preprocessor::CONTAINER_NAME ?> -v ${ROOT}:<?=$this->getWorkDir()?> <?= Preprocessor::IMAGE_NAME ?>:<?= $this->getImageTag() ?> /bin/bash
        fi
    else
        if [[ "${container}" =~ "Exited" ]]; then
            docker start <?= Preprocessor::CONTAINER_NAME ?> ;
        fi
        docker exec -it <?= Preprocessor::CONTAINER_NAME ?> /bin/bash
    fi
    exit 0
elif [ "$1" = "docker-commit" ] ;then
    docker commit <?= Preprocessor::CONTAINER_NAME ?> <?= Preprocessor::IMAGE_NAME ?>:<?= $this->getImageTag() ?> && exit 0
elif [ "$1" = "docker-commit" ] ;then
    docker push <?= Preprocessor::IMAGE_NAME ?>:<?= $this->getImageTag() ?> && exit 0
elif [ "$1" = "all-library" ] ;then
    make_all_library
<?php foreach ($this->libraryList as $item) : ?>
elif [ "$1" = "<?=$item->name?>" ] ;then
    make_<?=$item->name?> && echo "[SUCCESS] make <?=$item->name?>"
    exit 0
elif [ "$1" = "clean-<?=$item->name?>" ] ;then
    clean_<?=$item->name?> && echo "[SUCCESS] make clean <?=$item->name?>"
    exit 0
elif [ "$1" = "clean-<?=$item->name?>-cached" ] ;then
    clean_<?=$item->name?>_cached && echo "[SUCCESS] clean <?=$item->name?> "
    exit 0
<?php endforeach; ?>
elif [ "$1" = "config" ] ;then
    make_config
elif [ "$1" = "build" ] ;then
    make_build
elif [ "$1" = "test" ] ;then
    <?= BUILD_PHP_INSTALL_PREFIX ?>/bin/php vendor/bin/phpunit
elif [ "$1" = "archive" ] ;then
    cd <?= BUILD_PHP_INSTALL_PREFIX ?>/bin/
    PHP_VERSION=<?= BUILD_PHP_VERSION ?>
    PHP_CLI_FILE=php-micro-v${PHP_VERSION}-<?=$this->getOsType()?>-<?=$this->getSystemArch()?>.tar.xz
    cp -f micro.sfx  micro-dbg.sfx
    strip micro.sfx
    tar -cJvf ${PHP_CLI_FILE} micro.sfx
    mv ${PHP_CLI_FILE} <?= $this->workDir ?>/
    cd -
elif [ "$1" = "clean-all-library" ] ;then
<?php foreach ($this->libraryList as $item) : ?>
    clean_<?=$item->name?> && echo "[SUCCESS] make clean [<?=$item->name?>]"
<?php endforeach; ?>
    exit 0
elif [ "$1" = "clean-all-library-cached" ] ;then
<?php foreach ($this->libraryList as $item) : ?>
    echo "rm <?= $this->getBuildDir() ?>/<?= $item->name ?>/.completed"
    rm <?= $this->getBuildDir() ?>/<?= $item->name ?>/.completed
<?php endforeach; ?>
    exit 0
elif [ "$1" = "diff-configure" ] ;then
    meld $SRC/configure.ac ./configure.ac
elif [ "$1" = "list-swoole-branch" ] ;then
    cd <?= $this->getRootDir() ?>/sapi/swoole
    git branch
elif [ "$1" = "switch-swoole-branch" ] ;then
    cd <?= $this->getRootDir() ?>/sapi/swoole
    SWOOLE_BRANCH=$2
    git checkout $SWOOLE_BRANCH
elif [ "$1" = "pkg-check" ] ;then
<?php foreach ($this->libraryList as $item) : ?>
    <?php if (!empty($item->pkgNames)) : ?>
    echo "[<?= $item->name ?>] pkg-config : <?= implode(' ', $item->pkgNames) ?>" ;
    pkg-config --cflags-only-I --static <?= implode(' ', $item->pkgNames) . PHP_EOL ?>
    pkg-config --libs-only-L   --static <?= implode(' ', $item->pkgNames) . PHP_EOL ?>
    pkg-config --libs-only-l   --static <?= implode(' ', $item->pkgNames) . PHP_EOL ?>
    <?php else :?>
    echo "[<?= $item->name ?>] pkg-config : no "
    <?php endif ?>
    echo "==========================================================="

<?php endforeach; ?>
    exit 0
elif [ "$1" = "show-lib-pkg" ] ;then
    show_lib_pkg
    exit 0
elif [ "$1" = "show-lib-dep-pkg" ] ;then
    show_lib_dep_pkg "$2"
    exit 0
elif [ "$1" = "show-export-var" ] ;then
    show_export_var
    exit 0
elif [ "$1" = "list-library" ] ;then
<?php foreach ($this->libraryList as $item) : ?>
    echo "<?= $item->name ?>"
<?php endforeach; ?>
    exit 0
elif [ "$1" = "list-extension" ] ;then
<?php foreach ($this->extensionList as $item) : ?>
    echo "<?= $item->name ?>"
<?php endforeach; ?>
    exit 0
elif [ "$1" = "clean" ] ;then
    make_clean
    exit 0
elif [ "$1" = "sync" ] ;then
  echo "sync"
  # ZendVM
  cp -r $SRC/Zend ./
  # Extension
  cp -r $SRC/ext/bcmath/ ./ext
  cp -r $SRC/ext/bz2/ ./ext
  cp -r $SRC/ext/calendar/ ./ext
  cp -r $SRC/ext/ctype/ ./ext
  cp -r $SRC/ext/curl/ ./ext
  cp -r $SRC/ext/date/ ./ext
  cp -r $SRC/ext/dom/ ./ext
  cp -r $SRC/ext/exif/ ./ext
  cp -r $SRC/ext/fileinfo/ ./ext
  cp -r $SRC/ext/filter/ ./ext
  cp -r $SRC/ext/gd/ ./ext
  cp -r $SRC/ext/gettext/ ./ext
  cp -r $SRC/ext/gmp/ ./ext
  cp -r $SRC/ext/hash/ ./ext
  cp -r $SRC/ext/iconv/ ./ext
  cp -r $SRC/ext/intl/ ./ext
  cp -r $SRC/ext/json/ ./ext
  cp -r $SRC/ext/libxml/ ./ext
  cp -r $SRC/ext/mbstring/ ./ext
  cp -r $SRC/ext/mysqli/ ./ext
  cp -r $SRC/ext/mysqlnd/ ./ext
  cp -r $SRC/ext/opcache/ ./ext
  sed -i 's/ext_shared=yes/ext_shared=no/g' ext/opcache/config.m4 && sed -i 's/shared,,/$ext_shared,,/g' ext/opcache/config.m4
  sed -i 's/-DZEND_ENABLE_STATIC_TSRMLS_CACHE=1/-DZEND_ENABLE_STATIC_TSRMLS_CACHE=1 -DPHP_ENABLE_OPCACHE/g' ext/opcache/config.m4
  echo -e '#include "php.h"\n\nextern zend_module_entry opcache_module_entry;\n#define phpext_opcache_ptr  &opcache_module_entry\n' > ext/opcache/php_opcache.h
  cp -r $SRC/ext/openssl/ ./ext
  cp -r $SRC/ext/pcntl/ ./ext
  cp -r $SRC/ext/pcre/ ./ext
  cp -r $SRC/ext/pdo/ ./ext
  cp -r $SRC/ext/pdo_mysql/ ./ext
  cp -r $SRC/ext/pdo_sqlite/ ./ext
  cp -r $SRC/ext/phar/ ./ext
  echo -e '\n#include "sapi/cli/sfx/hook_stream.h"' >> ext/phar/phar_internal.h
  cp -r $SRC/ext/posix/ ./ext
  cp -r $SRC/ext/readline/ ./ext
  cp -r $SRC/ext/reflection/ ./ext
  cp -r $SRC/ext/session/ ./ext
  cp -r $SRC/ext/simplexml/ ./ext
  cp -r $SRC/ext/soap/ ./ext
  cp -r $SRC/ext/sockets/ ./ext
  cp -r $SRC/ext/sodium/ ./ext
  cp -r $SRC/ext/spl/ ./ext
  cp -r $SRC/ext/sqlite3/ ./ext
  cp -r $SRC/ext/standard/ ./ext
  cp -r $SRC/ext/sysvshm/ ./ext
  cp -r $SRC/ext/tokenizer/ ./ext
  cp -r $SRC/ext/xml/ ./ext
  cp -r $SRC/ext/xmlreader/ ./ext
  cp -r $SRC/ext/xmlwriter/ ./ext
  cp -r $SRC/ext/xsl/ ./ext
  cp -r $SRC/ext/zip/ ./ext
  cp -r $SRC/ext/zlib/ ./ext
  # main
  cp -r $SRC/main ./
  sed -i 's/\/\* start Zend extensions \*\//\/\* start Zend extensions \*\/\n#ifdef PHP_ENABLE_OPCACHE\n\textern zend_extension zend_extension_entry;\n\tzend_register_extension(\&zend_extension_entry, NULL);\n#endif/g' main/main.c
  # build
  cp -r $SRC/build ./
  # TSRM
  cp -r ./TSRM/TSRM.h main/TSRM.h
  cp -r $SRC/configure.ac ./
  # fpm
  cp -r $SRC/sapi/fpm/fpm ./sapi/cli
  exit 0
else
    help
fi

