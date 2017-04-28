<?php

namespace Drupal\config_installer\Tests;

use Drupal\Core\Archiver\ArchiveTar;

/**
 * Tests the config installer profile by linking to a directory.
 *
 * Note this test requires access to localise.drupal.org. Not managed to break
 * it any other way.
 *
 * @group ConfigInstaller
 */
class ConfigInstallerFrDirectorySyncTest extends ConfigInstallerTestBase {

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

    $this->drupalPostForm(NULL, ['sync_directory' => drupal_realpath($this->syncDir)], 'Save and continue');
  }

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
    $this->drupalPostForm(NULL, $edit, 'Enregistrer et continuer');
  }

  /**
   * Ensures that the user page is available after installation.
   */
  public function testInstaller() {
    // Do assertions from parent.
    require_once \Drupal::root() . '/core/includes/install.inc';
    $this->assertText('Félicitations, vous avez installé');
    $this->assertText(drupal_install_profile_distribution_name());

    // Even though we began the install in English the configuration is French
    // so that takes precedence.
    $this->assertEqual('fr', \Drupal::config('system.site')->get('default_langcode'));
    $this->assertFalse(\Drupal::service('language_manager')->isMultilingual());
  }

  /**
   * {@inheritdoc}
   */
  protected function getTarball() {
    // Exported configuration after a minimal profile install in French.
    return $this->versionTarball('missing-language-entity.tar.gz');
  }

}
