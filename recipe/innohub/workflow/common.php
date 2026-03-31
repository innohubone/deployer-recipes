<?php

namespace Deployer;

use Deployer\Task\Context;

require 'recipe/common.php';
require 'recipe/innohub/workflow/prepare.php';
require 'recipe/innohub/workflow/build.php';
require 'recipe/innohub/workflow/transfer.php';
require 'recipe/innohub/workflow/release.php';
require 'recipe/innohub/workflow/cleanup.php';

// === Global ==================================================================
set('allow_anonymous_stats', false);

// Set release name to a more readable name (ISO 8601)
set('release_name', function () {
    return date('c');
});
// By default keep the last 5 releases
set('keep_releases', 5);

/*
 * Main deploy task
 */
desc('Deploy your project');
task('deploy', [
    'prepare',
    'build',
    'transfer',
    'release',
    'cleanup',
]);

after('deploy:failed', 'deploy:unlock');

// === general =================================================================
set('general/root_dir', '{{release_path}}');
set('general/public_dir', '{{release_path}}/public');
set('general/hostname', function () {
    // @INFO: We need a function scope to get the proper context
    return Context::get()->getHost()->getAlias();
});
