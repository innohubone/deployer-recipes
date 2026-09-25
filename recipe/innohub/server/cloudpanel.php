<?php

namespace Deployer;

use Deployer\Exception\RunException;

require 'contrib/cachetool.php';
require 'recipe/innohub/server/nginx.php';

// Deploy to a CloudPanel site as its site user (= SSH user = PHP-FPM pool user), which has no sudo by default.
//
// Required per host:
//   ->set('php_version', '8.5')                 // must be set on the host, results in `bin/php` = /usr/bin/php8.5
//   ->set('cloudpanel/php_fpm_port', <port>)    // see vhost of the site: `fastcgi_pass 127.0.0.1:<port>;`
//   ->set('deploy_path', '/home/<site user>/htdocs/<domain>')
//
// One-time setup per site in CloudPanel:
// - Add the deploy SSH key to the site user
// - Vhost: use `$realpath_root`, so PHP picks up the new release right after the `current` symlink has been switched
//     fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
//     fastcgi_param DOCUMENT_ROOT $realpath_root;
// - Root Directory: `<domain>/current/public`, set it after the first deployment (`deploy:setup` fails, if `current` exists as a real directory)
// - Only if the project ships nginx configs in `config/nginx/*.conf`:
//     Vhost (server block): include /home/<site user>/htdocs/<domain>/nginx/*.conf;
//     /etc/sudoers.d/…:     <site user> ALL=(root) NOPASSWD: /usr/sbin/nginx -t, /usr/bin/systemctl reload nginx

// The site user owns all files, so there is no need for ACLs or chown
set('writable_mode', 'skip');

// === OPcache =================================================================
// Reset OPcache via FastCGI against the PHP-FPM pool of the site
set('cachetool', '127.0.0.1:{{cloudpanel/php_fpm_port}}');
set('cachetool_url', 'https://github.com/gordalina/cachetool/releases/download/10.0.0/cachetool.phar');

after('deploy:symlink', 'cachetool:clear:opcache');
after('rollback', 'cachetool:clear:opcache');

// === nginx ===================================================================
// nginx configs shipped with the project, relative to the release
set('cloudpanel/nginx/source_dir', 'config/nginx');
// Directory included by the vhost, outside of the releases, so it only changes after a successful config test
set('cloudpanel/nginx/config_dir', '{{deploy_path}}/nginx');
set('cloudpanel/nginx/test_command', 'sudo -n /usr/sbin/nginx -t');
set('cloudpanel/nginx/changed', false);

set('nginx:executable_path', '/usr/bin/systemctl');
set('nginx:reload_command', 'reload nginx');

/**
 * Activates the nginx configs of the given release and verifies the whole nginx config.
 * If the config test fails, the previous configs are restored, so nginx never ends up
 * with an invalid config on disk (which would affect all sites on the server).
 */
function activateCloudpanelNginxConfig(string $releasePath): void
{
    $source = $releasePath . '/{{cloudpanel/nginx/source_dir}}';
    $target = '{{cloudpanel/nginx/config_dir}}';

    // Project does not ship nginx configs (and never did): nothing to do, no sudo required
    if (!test("[ -d $source ]") && !test("[ -d $target ]")) {
        return;
    }

    // Build the new config next to the active one (empty, if the project removed its configs)
    run("rm -rf $target.new && mkdir -p $target.new");
    if (test("[ -d $source ]")) {
        run("cp -R $source/. $target.new/");
    }

    if (test("diff -r $target $target.new > /dev/null 2>&1")) {
        run("rm -rf $target.new");
        info('nginx configs unchanged');
        return;
    }

    run("rm -rf $target.old && if [ -d $target ]; then mv $target $target.old; fi && mv $target.new $target");
    try {
        // Wrapped, because Deployer treats any failing command starting with `sudo` as a missing password and prompts for one
        run('if ! {{cloudpanel/nginx/test_command}}; then exit 1; fi');
    } catch (RunException $exception) {
        run("rm -rf $target && if [ -d $target.old ]; then mv $target.old $target; fi");
        // Otherwise `rollback` would pick this release as candidate, although it never went live
        run("touch $releasePath/BAD_RELEASE");
        warning('nginx config test failed, previous nginx configs have been restored!');
        throw $exception;
    }
    run("rm -rf $target.old");

    set('cloudpanel/nginx/changed', true);
}

task('cloudpanel:nginx:config', function () {
    activateCloudpanelNginxConfig('{{release_path}}');
})->desc('Activate and test nginx configs of the new release')->hidden();

task('cloudpanel:nginx:config:rollback', function () {
    activateCloudpanelNginxConfig('{{current_path}}');
})->desc('Activate and test nginx configs of the current release')->hidden();

task('cloudpanel:nginx:reload', function () {
    if (get('cloudpanel/nginx/changed')) {
        invoke('nginx:reload');
    }
})->desc('Trigger nginx reload, if the nginx configs have been changed')->hidden();

// Test the new nginx configs before the new release goes live
before('deploy:symlink', 'cloudpanel:nginx:config');
after('deploy:symlink', 'cloudpanel:nginx:reload');

after('rollback', 'cloudpanel:nginx:config:rollback');
after('rollback', 'cloudpanel:nginx:reload');
