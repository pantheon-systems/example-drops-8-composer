<?php

namespace Drupal\Tests\Driver;

use Drupal\Driver\DrushDriver;

/**
 * Tests for the Drush driver.
 */
class DrushDriverTest extends \PHPUnit_Framework_TestCase {

  /**
   * Tests instantiating the driver with only an alias.
   */
  public function testWithAlias() {
    $driver = new DrushDriver('alias');
    $this->assertEquals('alias', $driver->alias, 'The drush alias was not properly set.');
  }

  /**
   * Tests instantiating the driver with a prefixed alias.
   */
  public function testWithAliasPrefix() {
    $driver = new DrushDriver('@alias');
    $this->assertEquals('alias', $driver->alias, 'The drush alias did not remove the "@" prefix.');
  }

  /**
   * Tests instantiating the driver with only the root path.
   */
  public function testWithRoot() {
    // Bit of a hack here to use the path to this file, but all the driver cares
    // about during initialization is that the root be a directory.
    $driver = new DrushDriver('', __FILE__);
    $this->assertEquals(__FILE__, $driver->root);
  }

  /**
   * Tests instantiating the driver with missing alias and root path.
   *
   * @expectedException \Drupal\Driver\Exception\BootstrapException
   */
  public function testWithNeither() {
    new DrushDriver('', '');
  }

}
