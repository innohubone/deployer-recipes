<?php

namespace Deployer;

desc('Release code on target hosts');
task('release', [
    'deploy:publish',
])
->hidden();

after('release', 'deploy:unlock');

task('deploy:publish')->hidden();

task('deploy:symlink')->hidden();
