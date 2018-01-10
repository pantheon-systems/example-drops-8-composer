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


// This is a silly way to detect if site install is happening and toggle the
// sync dir accordingly. @todo, I hope to think of a better option.
// Contenta install will fail if the sync dir is set to `/config`. But that is
// what the sync dir should be during normal usage.
if (function_exists('drush_get_arguments')) {
  $drush_args = drush_get_arguments();
  if (!empty($drush_args[0])  && 'site-install' === $drush_args[0]) {
    $config_directories['sync'] = 'profiles/contrib/contenta_jsonapi/config/sync';
  }
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
