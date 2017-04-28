<?php

namespace Drupal\config_installer\Tests;

/**
 * Tests the config installer profile by uploading a tarball.
 *
 * @group ConfigInstaller
 */
class ConfigInstallerTarballTest extends ConfigInstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUpSyncForm() {
    // Test some error situations.
    $this->drupalPostForm(NULL, ['files[import_tarball]' => $this->versionTarball('broken.tar.gz')], 'Save and continue');
    $this->assertText('Could not extract the contents of the tar file.');

    $this->drupalPostForm(NULL, ['files[import_tarball]' => $this->versionTarball('empty.tar.gz')], 'Save and continue');
    $this->assertText('The tar file contoins no files.');

    $this->drupalPostForm(NULL, ['files[import_tarball]' => $this->versionTarball('minimal-validation-fail.tar.gz')], 'Save and continue');
    $this->assertText('The tar file contoins no files.');
    $this->assertText('The configuration cannot be imported because it failed validation for the following reasons:');
    $this->assertText('Configuration foo.bar depends on the foo extension that will not be installed after import.');

    // Upload the tarball.
    $this->drupalPostForm(NULL, ['files[import_tarball]' => $this->getTarball()], 'Save and continue');
  }

}
