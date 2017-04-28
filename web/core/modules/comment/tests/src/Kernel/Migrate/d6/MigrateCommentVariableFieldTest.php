<?php

namespace Drupal\Tests\comment\Kernel\Migrate\d6;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade comment variables to field.storage.node.comment.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateCommentVariableFieldTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['comment'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['comment']);
    $this->migrateContentTypes();
    $this->executeMigrations(['d6_comment_type', 'd6_comment_field']);
  }

  /**
   * Tests comment variables migrated into a field entity.
   */
  public function testCommentField() {
    $this->assertTrue(is_object(FieldStorageConfig::load('node.comment')));
  }

}
