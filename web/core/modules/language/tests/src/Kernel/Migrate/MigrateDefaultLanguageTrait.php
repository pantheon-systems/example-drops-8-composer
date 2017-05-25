<?php

namespace Drupal\Tests\language\Kernel\Migrate;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Tests the default language variable migration.
 */
trait MigrateDefaultLanguageTrait {

  /**
   * Helper method to test the migration.
   *
   * @param string $langcode
   *   The langcode of the default language.
   * @param bool $existing
   *   Whether the default language exists on the destination.
   */
  protected function doTestMigration($langcode, $existing = TRUE) {
    // The default language of the test fixture is English. Change it to
    // something else before migrating, to be sure that the source site
    // default language is migrated.
    $value = 'O:8:"stdClass":11:{s:8:"language";s:2:"' . $langcode . '";s:4:"name";s:6:"French";s:6:"native";s:6:"French";s:9:"direction";s:1:"0";s:7:"enabled";i:1;s:7:"plurals";s:1:"0";s:7:"formula";s:0:"";s:6:"domain";s:0:"";s:6:"prefix";s:0:"";s:6:"weight";s:1:"0";s:10:"javascript";s:0:"";}';
    $this->sourceDatabase->update('variable')
      ->fields([
        'value' => $value
      ])
      ->condition('name', 'language_default' )
      ->execute();

    $this->startCollectingMessages();
    $this->executeMigrations(['language', 'default_language']);

    if ($existing) {
      // If the default language exists, we should be able to load it and the
      // default_langcode config should be set.
      $default_language = ConfigurableLanguage::load($langcode);
      $this->assertNotNull($default_language);
      $this->assertSame($langcode, $this->config('system.site')->get('default_langcode'));
    }
    else {
      // Otherwise, the migration log should contain an error message.
      $messages = $this->migration->getIdMap()->getMessageIterator();
      $count = 0;
      foreach ($messages as $message) {
        $count++;
        $this->assertSame($message->message, "The language '$langcode' does not exist on this site.");
        $this->assertSame((int) $message->level, MigrationInterface::MESSAGE_ERROR);
      }
      $this->assertSame($count, 1);
    }
  }

  /**
   * Helper method to test migrating the default language when no default language is set.
   */
  protected function doTestMigrationWithUnsetVariable() {
    // Delete the language_default variable.
    $this->sourceDatabase->delete('variable')
      ->condition('name', 'language_default' )
      ->execute();

    $this->startCollectingMessages();
    $this->executeMigrations(['language', 'default_language']);
    $messages = $this->migration->getIdMap()->getMessageIterator()->fetchAll();

    // Make sure there's no migration exceptions.
    $this->assertEmpty($messages);

    // Make sure the default langcode is 'en', since it was the default on D6 & D7.
    $this->assertSame('en', $this->config('system.site')->get('default_langcode'));
  }

}
