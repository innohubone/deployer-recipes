<?php

namespace Deployer;

require 'recipe/innohub/server/nginx.php';
require 'recipe/innohub/server/php-fpm.php';

set('nginx:executable_path', '/usr/local/etc/rc.d/nginx');
set('php-fpm:executable_path', '/usr/local/etc/rc.d/php-fpm');

after('deploy:symlink', 'nginx:reload');
after('deploy:symlink', 'php-fpm:reload');

after('rollback', 'nginx:reload');
after('rollback', 'php-fpm:reload');
