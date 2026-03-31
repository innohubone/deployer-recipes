<?php

namespace Deployer;

use Deployer\Exception\ConfigurationException;

set('source_path', function () {
    if (has('build_path')) {
        return '{{build_path}}/current';
    }

    $currentDirectory = \getcwd();
    if (! file_exists($currentDirectory . '/deploy.php')) {
        throw new ConfigurationException(
            'Could not determine path to deployment source directory ("source_path")',
            1512317992
        );
    }
    return $currentDirectory;
});
