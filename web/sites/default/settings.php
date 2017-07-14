<?php

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = __DIR__ . '/services.yml';

/**
 * Include the Pantheon-specific settings file.
 *
 * n.b. The settings.pantheon.php file makes some changes
 *      that affect all envrionments that this site
 *      exists in.  Always include this file, even in
 *      a local development environment, to ensure that
 *      the site settings remain consistent.
 */
include __DIR__ . "/settings.pantheon.php";

/**
 * Place the config directory outside of the Drupal root.
 */
$config_directories = array(
  CONFIG_SYNC_DIRECTORY => dirname(DRUPAL_ROOT) . '/config',
);

// Check to see if we are serving an installer page from the web server.
$is_installer_url = (strpos($_SERVER['SCRIPT_NAME'], '/core/install.php') === 0);
// Also check to see if we are calling the installer from a cli (e.g. Drush)
if (php_sapi_name() == 'cli') {
  global $install_state;
  if (isset(($install_state))) {
    $is_installer_url = true;
  }
}
if ($is_installer_url && !file_exists($config_directories[CONFIG_SYNC_DIRECTORY] . '/system.site.yml')) {
  // Contenta configuration:
  // Ideally, we keep our config export in ../config, but it needs to
  // be here at first so that installation will work.
  // TODO: Better strategy going forward for this.
  $config_directories[CONFIG_SYNC_DIRECTORY] = 'profiles/contrib/contenta_jsonapi/config/sync';
}

/**
 * If there is a local settings file, then include it
 */
$local_settings = __DIR__ . "/settings.local.php";
if (file_exists($local_settings)) {
  include $local_settings;
}

/**
 * We are going to install the contenta_jsonapi profile.
 */
$settings['install_profile'] = 'contenta_jsonapi';
