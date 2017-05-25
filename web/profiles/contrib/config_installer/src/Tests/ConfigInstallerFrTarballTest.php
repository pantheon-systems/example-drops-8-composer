<?php

namespace Drupal\config_installer\Tests;

/**
 * Tests the config installer profile by uploading a tarball.
 *
 * @group ConfigInstaller
 */
class ConfigInstallerFrTarballTest extends ConfigInstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUpSyncForm() {
    // Upload the tarball.
    $this->drupalPostForm(NULL, ['files[import_tarball]' => $this->getTarball()], 'Save and continue');
  }

  /**
   * Ensures that the user page is available after installation.
   */
  public function testInstaller() {
    // Do assertions from parent.
    require_once \Drupal::root() . '/core/includes/install.inc';
    $this->assertRaw(t('Congratulations, you installed @drupal fr!', [
      '@drupal' => drupal_install_profile_distribution_name(),
    ]));
    // Even though we began the install in English the configuration is French
    // so that takes precedence.
    $this->assertEqual('fr', \Drupal::config('system.site')->get('default_langcode'));
    $this->assertFalse(\Drupal::service('language_manager')->isMultilingual());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpLanguage() {
    // Place custom local translations in the translations directory so that we
    // don't go and translate everything.
    mkdir(DRUPAL_ROOT . '/' . $this->siteDirectory . '/files/translations', 0777, TRUE);
    file_put_contents(DRUPAL_ROOT . '/' . $this->siteDirectory . '/files/translations/drupal-8.0.0.fr.po', $this->getPo('fr'));

    parent::setUpLanguage();
  }

  /**
   * Returns the string for the test .po file.
   *
   * @param string $langcode
   *   The language code.
   *
   * @return string
   *   Contents for the test .po file.
   */
  protected function getPo($langcode) {
    return <<<ENDPO
msgid ""
msgstr ""

msgid "Congratulations, you installed @drupal!"
msgstr "Congratulations, you installed @drupal $langcode!"
ENDPO;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTarball() {
    // Exported configuration after a minimal profile install in French.
    return $this->versionTarball('minimal-fr.tar.gz');
  }

}
