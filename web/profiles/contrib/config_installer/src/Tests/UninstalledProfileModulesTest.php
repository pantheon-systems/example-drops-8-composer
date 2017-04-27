<?php

namespace Drupal\config_installer\Tests;

/**
 * Tests config installer when module dependencies of a profile are uninstalled.
 *
 * Profile dependencies are not true dependencies. After the Standard profile
 * has been installed it is possible to uninstall the Contact module even though
 * it is listed in the dependencies. Install profiles are special.
 *
 * @group ConfigInstaller
 */
class UninstalledProfileModulesTest extends ConfigInstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUpSyncForm() {
    // Upload the tarball.
    $this->drupalPostForm(NULL, ['files[import_tarball]' => $this->getTarball()], 'Save and continue');
  }

  /**
   * {@inheritdoc}
   */
  protected function getTarball() {
    // Exported configuration after a minimal profile install.
    return $this->versionTarball('standard-without-config.tar.gz');
  }

  /**
   * Runs tests after install.
   */
  public function testInstaller() {
    $this->assertResponse(200);
    // Ensure that all modules, profile and themes have been installed and have
    // expected weights.
    $sync = \Drupal::service('config.storage.sync');
    $sync_core_extension = $sync->read('core.extension');
    $this->assertIdentical($sync_core_extension, \Drupal::config('core.extension')->get());
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('contact'), 'Contact module is not installed.');
  }

}
