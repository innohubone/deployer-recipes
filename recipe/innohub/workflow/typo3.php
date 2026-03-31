<?php

namespace Deployer;

require 'recipe/innohub/workflow/common.php';
require 'recipe/innohub/utilities/composer_config.php';

// Unset env vars that affect build process
unset(
    $_ENV['TYPO3_CONTEXT'],
    $_ENV['TYPO3_PATH_ROOT'],
    $_ENV['TYPO3_PATH_WEB'],
    $_ENV['TYPO3_PATH_COMPOSER_ROOT'],
    $_ENV['TYPO3_PATH_APP']
);
putenv('TYPO3_CONTEXT');
putenv('TYPO3_PATH_ROOT');
putenv('TYPO3_PATH_WEB');
putenv('TYPO3_PATH_COMPOSER_ROOT');
putenv('TYPO3_PATH_APP');

// Extract TYPO3 root dir from composer config
set('typo3/root_dir', function () {
    // If no config is provided, we assume the root dir to be the release path
    $typo3RootDir = '.';
    $composerConfig = get('composer_config');
    if (isset($composerConfig['extra']['typo3/cms']['web-dir'])) {
        $typo3RootDir = $composerConfig['extra']['typo3/cms']['web-dir'];
    }
    if (isset($composerConfig['extra']['typo3/cms']['root-dir'])) {
        $typo3RootDir = $composerConfig['extra']['typo3/cms']['root-dir'];
    }
    return $typo3RootDir;
});

// Extract TYPO3 public directory from composer config
set('typo3/public_dir', function () {
    $composerConfig = get('composer_config');
    if (!isset($composerConfig['extra']['typo3/cms']['web-dir'])) {
        // If no config is provided, we assume the web dir to be the release path
        return '.';
    }
    return $composerConfig['extra']['typo3/cms']['web-dir'];
});

// overwrite general-vars
set('general/root_dir', '{{release_path}}/{{typo3/root_dir}}');
set('general/public_dir', '{{release_path}}/{{typo3/public_dir}}');

// Shared files/dirs between deploys
set('shared_dirs', [
    '{{typo3/public_dir}}/fileadmin',
    // @TODO: check whether the typo3temp/assets should be a shared directory or not
    // '{{typo3/root_dir}}/typo3temp/assets',
    '{{typo3/root_dir}}/fileadmin',
    '{{typo3/root_dir}}/uploads',

    // https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/11.4/Feature-93436-IntroduceCacheWarmupConsoleCommand.html#impact
    'var/charset',
    'var/labels',
    'var/lock',
    'var/log',
    'var/session',
]);

set('shared_files', [
    '.env',
]);

// Writeable directories
set('writable_dirs', [
    '{{typo3/root_dir}}/typo3temp/var/Cache',
    // These folders do not need to be made writeable on each deploy
    // but it is useful to make them writable on first deploy, so we keep them here
    '{{typo3/root_dir}}/fileadmin',
    '{{typo3/root_dir}}/uploads',
    '{{typo3/root_dir}}/typo3temp/assets',
]);
// These are server specific and should be set in the main deployment description
// See https://deployer.org/docs/flow#deploy:writable
//after('deploy:shared', 'deploy:writable');
//set('writable_mode', 'chmod');
//set('writable_chmod_recursive', true);
//set('writable_use_sudo', false);
//set('writable_chmod_mode', 'g+w');

set('log_files', implode(' ', [
    '{{typo3/root_dir}}/var/log/*.log',
    '{{typo3/root_dir}}/var/log/*/*.log',
]));

// Add TYPO3 directories to exclude them from rsync
add('rsync', [
    'exclude' => [
        '/bin',
        '/frontend',
        '/data',
        '/{{typo3/root_dir}}/fileadmin',
        '/{{typo3/root_dir}}/typo3temp',
        '/{{typo3/root_dir}}/uploads',
        'settings.local.yaml',
    ],
]);

add('maintenance/config', [
    'public/index.php' => [
        'entrypoint_filename' => 'public/index.php',
        'entrypoint_needle' => '<?php',
    ],
    'public/typo3/index.php' => [
        'entrypoint_filename' => 'public/typo3/index.php',
        'entrypoint_needle' => '<?php',
    ],
    'vendor/bin/typo3' => [
        'entrypoint_filename' => 'vendor/bin/typo3',
        'entrypoint_needle' => '<?php',
        'entrypoint_inject' => <<<EOT
            \$random = getenv('{{maintenance/randomName}}') ?: \$_SERVER['{{maintenance/randomName}}'] ?? '';
            if (file_exists('{{maintenance/lockFile}}') && \$random !== '{{maintenance/random}}') {
                echo 'maintenance mode!';
                exit(187);
            }
EOT
        ,
    ],
    'vendor/bin/typo3cms' => [
        'entrypoint_filename' => 'vendor/bin/typo3cms',
        'entrypoint_needle' => '<?php',
        'entrypoint_inject' => <<<EOT
            \$random = getenv('{{maintenance/randomName}}') ?: \$_SERVER['{{maintenance/randomName}}'] ?? '';
            if (file_exists('{{maintenance/lockFile}}') && \$random !== '{{maintenance/random}}') {
                echo 'maintenance mode!';
                exit(187);
            }
EOT
        ,
    ],
]);


set('bin/typo3', function (): string|null {
    if (test('[ -f {{composer_config/bin-dir}}/typo3cms ]')) {
        return '{{bin/php}} {{composer_config/bin-dir}}/typo3cms';
    }
    if (test('[ -f {{composer_config/bin-dir}}/typo3console ]')) {
        return '{{bin/php}} {{composer_config/bin-dir}}/typo3console';
    }
    if (test('[ -f {{composer_config/bin-dir}}/typo3 ]')) {
        return '{{bin/php}} {{composer_config/bin-dir}}/typo3';
    }
    if (test('[ -f {{release_path}}/typo3cms ]')) {
        return '{{bin/php}} {{release_path}}/typo3cms';
    }
    return null;
});

function runTypo3Cli(
    $command,
    array $arguments = [],
    array $options = [],
    ?bool $noThrow = false
): string {
    if (get('bin/typo3') === null) {
        output()->writeln(sprintf('<comment>Could not detect TYPO3 CLI command, skipping "%s"</comment>', $command));
        output()->writeln('<comment>Consider defining the path as "bin/typo3" within your Deployer configuration.</comment>');
        return '';
    }
    array_unshift($arguments, $command);

    $command = '{{bin/typo3}} ' . implode(' ', array_map('escapeshellarg', $arguments));
    return run(
        command: $command,
        options: $options,
        no_throw: $noThrow,
    );
}


desc('Flush caches');
task('typo3:flush:caches', function () {
    runTypo3Cli('cache:flush');
});

desc('Warmup caches');
task('typo3:warmup:caches', function () {
    runTypo3Cli('cache:warmup');
});

desc('Creates TYPO3 default folder structure');
task('typo3:create_default_folders', function () {
    runTypo3Cli('install:fixfolderstructure');
})->hidden();

desc('Update database schema');
task('typo3:update:databaseschema', function () {
    runTypo3Cli('database:updateschema', ['-v']);
});

desc('Set up TYPO3 extensions');
task('typo3:setup:extensions', function () {
    runTypo3Cli(command: 'extension:setup', arguments: ['-vvv'], noThrow: true);
})->hidden();

/** You may want to set this flag to `true` to always update language */
set('typo3/language/update/alwaysupdate', false);
set('typo3/language/update/timeout', 1800);

desc('Update languages');
task('typo3:language:update', function () {
    if (get('stage') !== 'production' && !get('typo3/language/update/alwaysupdate')) {
        writeln('<info>skipping because stage is not "production"!</info>');
        return;
    }

    runTypo3Cli('language:update', [], [
        'timeout' => (int)get('typo3/language/update/timeout'),
    ]);
});

/**
 * Add TYPO3 tasks predefined by TYPO3
 */
after('transfer', 'typo3:create_default_folders');
after('transfer', 'typo3:update:databaseschema');
after('transfer', 'typo3:flush:caches');
after('transfer', 'typo3:setup:extensions');
after('transfer', 'typo3:language:update');
// Make sure that `warmup:caches` will be hooked before `flush:caches`,
// to execute `flush:caches` first and then `warmup:caches`.
before('release', 'typo3:warmup:caches');
// The second `flush:cache` is required to properly read new translation labels!
before('release', 'typo3:flush:caches');
