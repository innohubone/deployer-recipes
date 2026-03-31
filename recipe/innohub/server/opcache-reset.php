<?php

namespace Deployer;

set('opcache/reset/phpCode', <<<EOT
<?php
die(function_exists('opcache_reset') ? opcache_reset() ? 'success' : 'opcache disabled' : 'not supported');

EOT
);
set('opcache/reset/filename', 'opcache-reset-'. md5(microtime()) .'.php');
set('opcache/reset/hostname', '{{general/hostname}}');

task('opcache:reset', function() {

    // first create a file with php code to clear opcache
    run('echo "{{opcache/reset/phpCode}}" > {{general/public_dir}}/{{opcache/reset/filename}}');

    // wait 5 seconds
    sleep(5);

    // and then try to execute that php file
    $result = runLocally('curl -Ls http://{{opcache/reset/hostname}}/{{opcache/reset/filename}}');

    // If executing the file from remote won't work try to execute from the server itself
    if ($result !== 'success') {
        info('Could not clear caches with "runLocally", trying "run" directly on server instead!'."\n");

        $result = run('curl -Ls http://{{general/hostname}}/{{opcache/reset/filename}}');
    }

    // If clearing the opcache didn't work at all, notify the user that something went wrong
    if ($result !== 'success') {
        warning('Could not reliably reset opcode cache!' . "\n");
        warning('Error: '. substr(strip_tags($result), 0, 500) . "\n");
        warning('This wont trigger an error intentionally! You may need to clear opcode cache manually!' . "\n");
    }

    // Don't forget to remove the generated file
    run('rm {{general/public_dir}}/{{opcache/reset/filename}}');

})->desc('Create php file to reset opcache');


// You may need to add the following line to your configuration manually!
// after('deploy:symlink', 'opcache:reset');
