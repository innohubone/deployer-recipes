<?php

namespace Deployer;

require_once 'recipe/innohub/utilities/functions.php';

desc('Prepares a new release');
task('prepare', [
    'deploy:check_remote',
    'deploy:info',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'deploy:writable',
])
->hidden();

task('deploy:setup')->hidden();
task('deploy:release')->hidden();
task('deploy:writable')->hidden();

set('deployment_information_file', '{{typo3/root_dir}}/deployment-information.json');

task('prepare:provide_deployment_information', function () {
    $deploymentInformation = [
        'revision' => getGitCommitHash(),
        'tagOrBranch' => getGitTagOrBranch(),
        'releaseName' => get('release_name'),
        'releasePath' => get('release_path'),
    ];

    var_dump(get('typo3/root_dir'));
    var_dump(get('deployment_information_file'));

    file_put_contents(
        get('deployment_information_file'),
        json_encode($deploymentInformation, \JSON_THROW_ON_ERROR)
    );
})
->hidden();
// after('deploy:release', 'prepare:provide_deployment_information');
