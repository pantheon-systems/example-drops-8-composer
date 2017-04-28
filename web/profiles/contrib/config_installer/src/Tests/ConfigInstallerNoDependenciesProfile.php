<?php

namespace Drupal\config_installer\Tests;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Config\FileStorage;

/**
 * Tests the config installer profile with a profile with no dependencies.
 *
 * @group ConfigInstaller
 */
class ConfigInstallerNoDependenciesProfile extends ConfigInstallerTestBase {

  protected function setUp() {
    $this->info = [
      'type' => 'profile',
      'core' => \Drupal::CORE_COMPATIBILITY,
      'name' => 'Profile with no dependencies',
    ];
    // File API functions are not available yet.
    $path = $this->siteDirectory . '/profiles/no_dependencies_profile';
    mkdir($path, 0777, TRUE);
    file_put_contents("$path/no_dependencies_profile.info.yml", Yaml::encode($this->info));

    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSyncForm() {
    $this->drupalPostForm(NULL, ['files[import_tarball]' => $this->versionTarball('no_dependencies_profile.tar.gz')], 'Save and continue');
  }

}
