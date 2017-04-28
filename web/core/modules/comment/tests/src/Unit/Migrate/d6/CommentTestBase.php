<?php

namespace Drupal\Tests\comment\Unit\Migrate\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Base class for comment source unit tests.
 */
abstract class CommentTestBase extends MigrateSqlSourceTestCase {

  // The plugin system is not working during unit testing so the source plugin
  // class needs to be manually specified.
  const PLUGIN_CLASS = 'Drupal\comment\Plugin\migrate\source\d6\Comment';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = [
    // The ID of the entity, can be any string.
    'id' => 'test',
    // This needs to be the identifier of the actual key: cid for comment, nid
    // for node and so on.
    'source' => [
      'plugin' => 'd6_comment',
    ],
  ];

  // We need to set up the database contents; it's easier to do that below.

  protected $expectedResults = [
    [
      'cid' => 1,
      'pid' => 0,
      'nid' => 2,
      'uid' => 3,
      'subject' => 'subject value 1',
      'comment' => 'comment value 1',
      'hostname' => 'hostname value 1',
      'timestamp' => 1382255613,
      'status' => 1,
      'thread' => '',
      'name' => '',
      'mail' => '',
      'homepage' => '',
      'format' => 'testformat1',
      'type' => 'story',
    ],
    [
      'cid' => 2,
      'pid' => 1,
      'nid' => 3,
      'uid' => 4,
      'subject' => 'subject value 2',
      'comment' => 'comment value 2',
      'hostname' => 'hostname value 2',
      'timestamp' => 1382255662,
      'status' => 1,
      'thread' => '',
      'name' => '',
      'mail' => '',
      'homepage' => '',
      'format' => 'testformat2',
      'type' => 'page',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    foreach ($this->expectedResults as $k => $row) {
      $this->databaseContents['comments'][$k] = $row;
      $this->databaseContents['comments'][$k]['status'] = 1 - $this->databaseContents['comments'][$k]['status'];
    }
    // Add node table data.
    $this->databaseContents['node'][] = ['nid' => 2, 'type' => 'story'];
    $this->databaseContents['node'][] = ['nid' => 3, 'type' => 'page'];
    parent::setUp();
  }

}
