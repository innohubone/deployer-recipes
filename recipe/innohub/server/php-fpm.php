<?php

namespace Deployer;

// Set to true if `sudo` should be prefixed
set('php-fpm:use_sudo', true);
// Enter the absolute path to the executable
set('php-fpm:executable_path', '/usr/sbin/nginx');
// You should prefer `reload` instead of `restart`. Just in case somethings fails…
set('php-fpm:reload_command', 'reload');

task('php-fpm:reload', function () {
    // Make sure that the executing user has permissions to run this command!
    $command = sprintf(
        '%s%s %s',
        get('php-fpm:use_sudo') ? 'sudo ' : '',
        get('php-fpm:executable_path'),
        get('php-fpm:reload_command')
    );

    run($command);
})->desc('Trigger php-fpm reload');

// You need to add the following line to your configuration manually!
// after('deploy:symlink', 'php-fpm:reload');
