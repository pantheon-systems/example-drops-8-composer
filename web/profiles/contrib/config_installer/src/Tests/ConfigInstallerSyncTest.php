<?php

namespace Drupal\config_installer\Tests;

use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Config\FileStorage;

/**
 * Tests the config installer profile by having files in a sync directory.
 *
 * @group ConfigInstaller
 */
class ConfigInstallerSyncTest extends ConfigInstallerTestBase {

  /**
   * The directory where the configuration to install is stored.
   *
   * @var string
   */
  protected $syncDir;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->syncDir = 'public://' . $this->randomMachineName(128);
    parent::setUp();
  }

  /**
   * Ensures that the user page is available after installation.
   */
  public function testInstaller() {
    // Do assertions from parent.
    parent::testInstaller();

    // Do assertions specific to test.
    $this->assertEqual(drupal_realpath($this->syncDir), config_get_config_directory(CONFIG_SYNC_DIRECTORY), 'The sync directory has been updated during the installation.');
    $this->assertEqual(USER_REGISTER_ADMINISTRATORS_ONLY, \Drupal::config('user.settings')->get('register'), 'Ensure standard_install() does not overwrite user.settings::register.');
    $this->assertEqual([], \Drupal::entityDefinitionUpdateManager()->getChangeSummary(), 'There are no entity or field definition updates.');
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSyncForm() {
    // Create a new sync directory.
    drupal_mkdir($this->syncDir);

    // Extract the tarball into the sync directory.
    $archiver = new ArchiveTar($this->getTarball(), 'gz');
    $files = [];
    foreach ($archiver->listContent() as $file) {
      $files[] = $file['filename'];
    }
    $archiver->extractList($files, $this->syncDir);

    // Change the user.settings::register so that we can test that
    // standard_install() does not override it.
    $sync = new FileStorage($this->syncDir);
    $user_settings = $sync->read('user.settings');
    $user_settings['register'] = USER_REGISTER_ADMINISTRATORS_ONLY;
    $sync->write('user.settings', $user_settings);

    // Create config for a module that will not be enabled.
    $sync->write('foo.bar', []);
    $this->drupalPostForm(NULL, ['sync_directory' => drupal_realpath($this->syncDir)], 'Save and continue');
    $this->assertText('The configuration cannot be imported because it failed validation for the following reasons:');
    $this->assertText('Configuration foo.bar depends on the foo extension that will not be installed after import.');

    // Remove incorrect config and continue on.
    $sync->delete('foo.bar');
    $this->drupalPostForm(NULL, ['sync_directory' => drupal_realpath($this->syncDir)], 'Save and continue');
  }

}
