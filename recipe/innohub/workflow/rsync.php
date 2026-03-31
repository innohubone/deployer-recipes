<?php

namespace Deployer;

require 'contrib/rsync.php';
require 'recipe/innohub/utilities/source_path.php';

// === custom overwrites =======================================================
task('rsync')->hidden();
task('rsync:warmup')->hidden();

set('rsync_src', '{{source_path}}');
set('rsync_dest', '{{release_path}}');

set('rsync', [
    'exclude' => [
        'var/log',
        '.vscode',
        '.env',
        '.env*',
        '.ddev',
        '.idea',
        'auth.json',
        'auth*.json',
        '.git*',
        '.DS_*',
        '*.sublime-*',
        '*.map',
        '*.bak',
        '*.log',
        'logs',
        'info.php',
        'README.md',
        'INSTALL.md',
        'node_modules',
        '.maintenance.lock',
    ],
    'exclude-file' => false,
    'include' => [],
    'include-file' => false,
    'filter' => [],
    'filter-file' => false,
    'filter-perdir' => false,
    'flags' => 'rz',
    'options' => [
        'times',
        'perms',
        'links',
        'delete',
        'delete-excluded',
    ],
    'timeout' => 600,
]);
