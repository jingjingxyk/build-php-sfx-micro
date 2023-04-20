#!/usr/bin/env php
<?php
require __DIR__ . '/vendor/autoload.php';

use SwooleCli\Preprocessor;

$homeDir = getenv('HOME');
$p = Preprocessor::getInstance();
$p->parseArguments($argc, $argv);


// Sync code from php-src
$p->setPhpSrcDir($homeDir . '/.phpbrew/build/php-8.1.12');

// Compile directly on the host machine, not in the docker container
if ($p->getInputOption('without-docker')) {
    $p->setWorkDir(__DIR__);
    $p->setBuildDir(__DIR__ . '/thirdparty');
    $p->setGlobalPrefix($homeDir . '/.swoole-cli');
}

$build_type = $p->getInputOption('with-build-type');
if (!in_array($build_type, ['dev', 'debug'])) {
    $build_type = 'release';
}
define('SWOOLE_CLI_BUILD_TYPE', $build_type);
define('SWOOLE_CLI_GLOBAL_PREFIX', $p->getGlobalPrefix());


if ($p->getOsType() == 'macos') {
    $p->setExtraLdflags('-undefined dynamic_lookup');
}

$p->setExtraCflags('-fno-ident -Os');

// Generate make.sh
$p->execute();
