<?php

namespace Drupal\Tests\comment\Kernel\Migrate\d6;

use Drupal\comment\Entity\CommentType;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade comment type.
 *
 * @group migrate_drupal_6
 */
class MigrateCommentTypeTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['comment'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installConfig(['node', 'comment']);
    $this->executeMigration('d6_comment_type');
  }

  /**
   * Tests the Drupal 6 to Drupal 8 comment type migration.
   */
  public function testCommentType() {
    $comment_type = CommentType::load('comment');
    $this->assertIdentical('node', $comment_type->getTargetEntityTypeId());
    $comment_type = CommentType::load('comment_no_subject');
    $this->assertIdentical('node', $comment_type->getTargetEntityTypeId());
  }

}
