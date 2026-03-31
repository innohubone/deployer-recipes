<?php

namespace Deployer;

require 'recipe/innohub/utilities/source_path.php';

// Fetch composer.json and store it in array for later use
set('composer_config', function () {
    $composerJsonPath = parse('{{source_path}}/composer.json');
    if (!file_exists($composerJsonPath)) {
        // If we don't find a composer.json file, we assume the root dir to be the release path
        return null;
    }
    return \json_decode(\file_get_contents($composerJsonPath), true);
});

// Extract bin-dir from composer config
set('composer_config/bin-dir', function () {
    $binDir = '{{release_path}}/vendor/bin';
    $composerConfig = get('composer_config');
    if (isset($composerConfig['config']['bin-dir'])) {
        $binDir = '{{release_path}}/' . $composerConfig['config']['bin-dir'];
    }
    return $binDir;
});
