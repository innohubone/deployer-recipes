<?php

namespace Deployer;

// Set to true if `sudo` should be prefixed
set('nginx:use_sudo', true);
// Enter the absolute path to the executable
set('nginx:executable_path', '/usr/local/etc/rc.d/nginx');
// You should prefer `reload` instead of `restart`. Just in case somethings fails…
set('nginx:reload_command', 'reload');

task('nginx:reload', function () {
    // Make sure that the executing user has permissions to run this command!
    $command = sprintf(
        '%s%s %s',
        get('nginx:use_sudo') ? 'sudo ' : '',
        get('nginx:executable_path'),
        get('nginx:reload_command')
    );

    run($command);
})->desc('Trigger nginx reload');

// You need to add the following line to your configuration manually!
// after('deploy:symlink', 'nginx:reload');
