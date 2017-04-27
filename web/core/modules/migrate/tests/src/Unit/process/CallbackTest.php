<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\process\CallbackTest.
 */

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\Plugin\migrate\process\Callback;

/**
 * Tests the callback process plugin.
 *
 * @group migrate
 */
class CallbackTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->plugin = new TestCallback();
    parent::setUp();
  }

  /**
   * Test callback with a function as callable.
   */
  public function testCallbackWithFunction() {
    $this->plugin->setCallable('strtolower');
    $value = $this->plugin->transform('FooBar', $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, 'foobar');
  }

  /**
   * Test callback with a class method as callable.
   */
  public function testCallbackWithClassMethod() {
    $this->plugin->setCallable(['\Drupal\Component\Utility\Unicode', 'strtolower']);
    $value = $this->plugin->transform('FooBar', $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, 'foobar');
  }

}

class TestCallback extends Callback {
  public function __construct() {
  }

  public function setCallable($callable) {
    $this->configuration['callable'] = $callable;
  }

}
