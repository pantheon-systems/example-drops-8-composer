<?php

/**
 * To make site-install on an already-installed site possible, we
 * need to attempt to reverse the chmod's done by Drupal's install
 * process, so that the the 'default' directory and 'settings.php'
 * file are writable again, if possible.
 *
 * If we cannot make settings.php writable, then we will do nothing,
 * and let the installer report errors.
 */
function drush_reinstall_site_install_validate() {
    // Find the $conf_path the same way that drush site-install does
    $alias_record = drush_sitealias_get_record('@self');
    $sites_subdir = drush_sitealias_local_site_path($alias_record);
    // Override with sites-subdir if specified.
    if ($dir = drush_get_option('sites-subdir')) {
        $sites_subdir = "sites/$dir";
    }
    $conf_path = $sites_subdir;
    $settingsfile = "$conf_path/settings.php";

    // If the conf directory exists but is not writable, try to make it writable
    if (is_dir($conf_path) && (!is_writable($conf_path))) {
        @chmod($conf_path, 0777);
    }
    // If the settings file exists but is not writable, try to make it writable
    if (file_exists($settingsfile) && !is_writable($settingsfile)) {
        @chmod($settingsfile, 0777);
    }
}
