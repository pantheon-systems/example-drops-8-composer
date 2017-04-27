<?php

namespace Drupal\config_installer\Tests;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Site\Settings;
use Drupal\simpletest\InstallerTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides functionality for testing the config_installer profile.
 */
abstract class ConfigInstallerTestBase extends InstallerTestBase {

  /**
   * The installation profile to install.
   *
   * @var string
   */
  protected $profile = 'config_installer';

  /**
   * Ensures that the user page is available after installation.
   */
  public function testInstaller() {
    $this->assertUrl('user/1');
    $this->assertResponse(200);
    // Confirm that we are logged-in after installation.
    $this->assertText($this->rootUser->getUsername());

    // @todo hmmm this message is wrong!
    // Verify that the confirmation message appears.
    require_once \Drupal::root() . '/core/includes/install.inc';
    $this->assertRaw(t('Congratulations, you installed @drupal!', [
      '@drupal' => drupal_install_profile_distribution_name(),
    ]));

    // Ensure that all modules, profile and themes have been installed and have
    // expected weights.
    $sync = \Drupal::service('config.storage.sync');
    $sync_core_extension = $sync->read('core.extension');
    $this->assertIdentical($sync_core_extension, \Drupal::config('core.extension')->get());

    // Ensure that the correct install profile has been written to settings.php.
    $listing = new ExtensionDiscovery(\Drupal::root());
    $listing->setProfileDirectories([]);
    $profiles = array_intersect_key($listing->scan('profile'), $sync_core_extension['module']);
    $current_profile = Settings::get('install_profile');
    $this->assertFalse(empty($current_profile), 'The $install_profile setting exists');
    $this->assertEqual($current_profile, key($profiles));

    // Test that any configuration entities in sync have been created.
    // @todo
  }

  /**
   * Overrides method.
   *
   * We have several forms to navigate through.
   */
  protected function setUpSite() {
    // Recreate the container so that we can simulate the submission of the
    // SyncConfigureForm after the full bootstrap has occurred. Without out this
    // drupal_realpath() does not work so uploading files through
    // WebTestBase::postForm() is impossible.
    $request = Request::createFromGlobals();
    $class_loader = require $this->container->get('app.root') . '/vendor/autoload.php';
    Settings::initialize($this->container->get('app.root'), DrupalKernel::findSitePath($request), $class_loader);
    foreach ($GLOBALS['config_directories'] as $type => $path) {
      $this->configDirectories[$type] = $path;
    }
    $this->kernel = DrupalKernel::createFromRequest($request, $class_loader, 'prod', FALSE);
    $this->kernel->prepareLegacyRequest($request);
    $this->container = $this->kernel->getContainer();

    $this->setUpSyncForm();
    $this->setUpInstallConfigureForm();
    // If we've got to this point the site is installed using the regular
    // installation workflow.
    $this->isInstalled = TRUE;
  }

  /**
   * Submit the config_installer_sync_configure_form.
   *
   * @see \Drupal\config_installer\Form\SyncConfigureForm
   */
  abstract protected function setUpSyncForm();

  /**
   * Submit the config_installer_site_configure_form.
   *
   * @see \Drupal\config_installer\Form\SiteConfigureForm
   */
  protected function setUpInstallConfigureForm() {
    $params = $this->parameters['forms']['install_configure_form'];
    unset($params['site_name']);
    unset($params['site_mail']);
    unset($params['update_status_module']);
    $edit = $this->translatePostValues($params);
    $this->drupalPostForm(NULL, $edit, $this->translations['Save and continue']);
  }

  /**
   * Gets the tarball for testing.
   *
   * @var string
   */
  protected function getTarball() {
    // Exported configuration after a minimal profile install.
    return $this->versionTarball('minimal.tar.gz');
  }

  /**
   * Gets a tarball for the right version of Drupal.
   *
   * @param $tarball
   *   The tarball filename.
   *
   * @return string
   *   The fullpath to the tarball.
   */
  protected function versionTarball($tarball) {
    include_once \Drupal::root() . '/core/includes/install.core.inc';
    $version = _install_get_version_info(\Drupal::VERSION);
    $versioned_file = __DIR__ . '/Fixtures/' . $version['major'] . '.' . $version['minor'] . '/' . $tarball;
    if (file_exists($versioned_file)) {
      return $versioned_file;
    }
    return __DIR__ . '/Fixtures/' . $tarball;
  }

}
