<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Kernel\SqlBaseTest.
 */

namespace Drupal\Tests\migrate\Kernel;

use Drupal\migrate\Plugin\migrate\source\TestSqlBase;
use Drupal\Core\Database\Database;

/**
 * Tests the functionality of SqlBase.
 *
 * @group migrate
 */
class SqlBaseTest extends MigrateTestBase {

  /**
   * Tests different connection types.
   */
  public function testConnectionTypes() {
    $sql_base = new TestSqlBase();

    // Check the default values.
    $sql_base->setConfiguration([]);
    $this->assertIdentical($sql_base->getDatabase()->getTarget(), 'default');
    $this->assertIdentical($sql_base->getDatabase()->getKey(), 'migrate');

    $target = 'test_db_target';
    $key = 'test_migrate_connection';
    $config = ['target' => $target, 'key' => $key];
    $sql_base->setConfiguration($config);
    Database::addConnectionInfo($key, $target, Database::getConnectionInfo('default')['default']);

    // Validate we have injected our custom key and target.
    $this->assertIdentical($sql_base->getDatabase()->getTarget(), $target);
    $this->assertIdentical($sql_base->getDatabase()->getKey(), $key);

    // Now test we can have SqlBase create the connection from an info array.
    $sql_base = new TestSqlBase();

    $target = 'test_db_target2';
    $key = 'test_migrate_connection2';
    $database = Database::getConnectionInfo('default')['default'];
    $config = ['target' => $target, 'key' => $key, 'database' => $database];
    $sql_base->setConfiguration($config);

    // Call getDatabase() to get the connection defined.
    $sql_base->getDatabase();

    // Validate the connection has been created with the right values.
    $this->assertIdentical(Database::getConnectionInfo($key)[$target], $database);

    // Now, test this all works when using state to store db info.
    $target = 'test_state_db_target';
    $key = 'test_state_migrate_connection';
    $config = ['target' => $target, 'key' => $key];
    $database_state_key = 'migrate_sql_base_test';
    \Drupal::state()->set($database_state_key, $config);
    $sql_base->setConfiguration(['database_state_key' => $database_state_key]);
    Database::addConnectionInfo($key, $target, Database::getConnectionInfo('default')['default']);

    // Validate we have injected our custom key and target.
    $this->assertIdentical($sql_base->getDatabase()->getTarget(), $target);
    $this->assertIdentical($sql_base->getDatabase()->getKey(), $key);

    // Now test we can have SqlBase create the connection from an info array.
    $sql_base = new TestSqlBase();

    $target = 'test_state_db_target2';
    $key = 'test_state_migrate_connection2';
    $database = Database::getConnectionInfo('default')['default'];
    $config = ['target' => $target, 'key' => $key, 'database' => $database];
    $database_state_key = 'migrate_sql_base_test2';
    \Drupal::state()->set($database_state_key, $config);
    $sql_base->setConfiguration(['database_state_key' => $database_state_key]);

    // Call getDatabase() to get the connection defined.
    $sql_base->getDatabase();

    // Validate the connection has been created with the right values.
    $this->assertIdentical(Database::getConnectionInfo($key)[$target], $database);
  }

}

namespace Drupal\migrate\Plugin\migrate\source;

/**
 * A dummy source to help with testing SqlBase.
 *
 * @package Drupal\migrate\Plugin\migrate\source
 */
class TestSqlBase extends SqlBase {

  /**
   * Overrides the constructor so we can create one easily.
   */
  public function __construct() {
    $this->state = \Drupal::state();
  }

  /**
   * Gets the database without caching it.
   */
  public function getDatabase() {
    $this->database = NULL;
    return parent::getDatabase();
  }

  /**
   * Allows us to set the configuration from a test.
   *
   * @param array $config
   *   The config array.
   */
  public function setConfiguration($config) {
    $this->configuration = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {}

  /**
   * {@inheritdoc}
   */
  public function fields() {}

  /**
   * {@inheritdoc}
   */
  public function query() {}

}
