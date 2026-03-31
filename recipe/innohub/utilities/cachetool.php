<?php

namespace Deployer;

require 'contrib/cachetool.php';

set('cachetool_url', function () {
    // @see https://github.com/gordalina/cachetool#version-compatibility
    // @see https://github.com/gordalina/cachetool/releases
    // @see https://github.com/gordalina/cachetool/tags
    $cachetool = <<<EOT
echo version_compare(phpversion(), '8.1', '>=')
    ? 'https://github.com/gordalina/cachetool/releases/download/9.2.1/cachetool.phar'
    : (
        version_compare(phpversion(), '8.0', '>=')
        ? 'https://github.com/gordalina/cachetool/releases/download/8.6.1/cachetool.phar'
        : (
            version_compare(phpversion(), '7.3', '>=')
            ? 'https://github.com/gordalina/cachetool/releases/download/7.1.0/cachetool.phar'
            : (
                version_compare(phpversion(), '7.2', '>=')
                ? 'https://github.com/gordalina/cachetool/releases/download/5.1.3/cachetool.phar'
                : (
                    version_compare(phpversion(), '7.1', '>=')
                    ? 'https://gordalina.github.io/cachetool/downloads/cachetool-4.1.1.phar'
                    : 'https://gordalina.github.io/cachetool/downloads/cachetool-3.2.2.phar'
                )
            )
        )
    )
;
EOT;

    return run("{{bin/php}} -r \"$cachetool\"");
});

// Overwrite default `bin/cachetool`, to have a unified output file and make sure that the file can be executed
set('bin/cachetool', function () {
    if (! test('[ -f {{release_or_current_path}}/cachetool.phar ]')) {
        run('cd {{release_or_current_path}} && curl -sL {{cachetool_url}} -o cachetool.phar');
        run('chmod +x {{release_or_current_path}}/cachetool.phar');
    }
    return '{{release_or_current_path}}/cachetool.phar';
});

// Make sure that OPcache will be cleared
before('release', 'cachetool:clear:opcache');
