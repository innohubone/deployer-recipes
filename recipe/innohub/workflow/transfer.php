<?php

namespace Deployer;

require 'recipe/innohub/workflow/rsync.php';

desc('Transfer code to target hosts');
task('transfer', [
    'rsync:warmup',
    'rsync',
    'deploy:shared',
])
->hidden();

task('deploy:shared')->hidden();

task('deploy:copy_dirs')->hidden();
task('deploy:clear_paths')->hidden();
