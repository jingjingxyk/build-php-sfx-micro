<?php

use SwooleCli\Library;
use SwooleCli\Preprocessor;
use SwooleCli\Extension;

return function (Preprocessor $p) {
    $oniguruma_prefix = ONIGURUMA_PREFIX;
    $p->addLibrary(
        (new Library('oniguruma'))
            ->withHomePage('https://github.com/kkos/oniguruma.git')
            ->withUrl('https://codeload.github.com/kkos/oniguruma/tar.gz/refs/tags/v6.9.7')
            ->withPrefix($oniguruma_prefix)
            ->withConfigure(
                './autogen.sh && ./configure --prefix=' . $oniguruma_prefix . ' --enable-static --disable-shared'
            )
            ->withFile('oniguruma-6.9.7.tar.gz')
            ->withLicense('https://github.com/kkos/oniguruma/blob/master/COPYING', Library::LICENSE_SPEC)
            ->withPkgName('oniguruma')
            ->withBinPath($oniguruma_prefix . '/bin/')
    );
    $p->withExportVariable('ONIG_CFLAGS', '$(pkg-config --cflags --static oniguruma)');
    $p->withExportVariable('ONIG_LIBS', '$(pkg-config   --libs   --static oniguruma)');
    $p->addExtension(
        (new Extension('mbstring'))
            ->withHomePage('https://www.php.net/mbstring')
            ->withOptions('--enable-mbstring')
            ->depends('oniguruma')
    );
};
