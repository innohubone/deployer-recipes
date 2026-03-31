<?php

namespace Deployer;

require 'contrib/sentry.php';
require_once 'recipe/innohub/utilities/functions.php';

function getSentryEnvironment(): \Closure
{
    return static function ($config = []): string {
        $labels = currentHost()->getLabels();
        $stage = $labels['stage'] ?? null;

        // The returning values should be the same as defined in $TYPO3_CONTEXT ENV var, where slashes should be replaced with dashes

        switch ($stage) {
            case 'development':
                return 'Development';
            case 'testing':
                return 'Development-Testing';

            case 'staging':
                return 'Production-Staging';
            case 'production':
                return 'Production-Production';
        }

        return null;
    };
}

// You need to add this to your configuration manually!
//
// require_once 'recipe/kandoh/utilities/sentry.php';
//
// set('sentry', [
//     'organization' => 'xxxxxxxx',
//     'projects' => [
//         'xxxxxxxxxxx',
//     ],
//     'token' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
//     'version' => getGitTagOrCommitHash(),
//     'environment' => getSentryEnvironment(),
//     'commits' => null,
// ]);
//
// after('deploy:success', 'deploy:sentry');
