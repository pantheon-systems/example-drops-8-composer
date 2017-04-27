<?php

namespace Drupal\Tests\Driver;

/**
 * Base class for field handler tests.
 */
abstract class FieldHandlerAbstractTest extends \PHPUnit_Framework_TestCase {

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    \Mockery::close();
  }

  /**
   * Factory method to build and returned a mocked field handler.
   *
   * @param string $handler
   *   The name of the field handler class under test.
   * @param object $entity
   *   An object representing an entity. Should contain a single property which
   *   represents a field containing a value.
   * @param string $entity_type
   *   The entity type under test.
   * @param array $field
   *   An associative array with the following keys:
   *   - 'field_name': the field name that is used for the property on $entity.
   *   - 'columns': an optional array containing the column names of the field
   *     as keys.
   *
   * @return \Mockery\MockInterface
   *   The mocked field handler.
   */
  protected function getMockHandler($handler, $entity, $entity_type, array $field) {
    $mock = \Mockery::mock(sprintf('Drupal\Driver\Fields\Drupal7\%s', $handler));
    $mock->makePartial();
    $mock->shouldReceive('getFieldInfo')->andReturn($field);
    $mock->shouldReceive('getEntityLanguage')->andReturn('en');
    $mock->__construct($entity, $entity_type, $field);

    return $mock;
  }

  /**
   * Simulate __call() since mocked handlers will not run through magic methods.
   *
   * @param mixed $values
   *   The field value(s).
   *
   * @return array
   *   The values parameter cast to an array.
   */
  protected function values($values) {
    return (array) $values;
  }

}
