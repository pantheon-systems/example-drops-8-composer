<?php

namespace Drupal\Driver\Fields\Drupal8;

use Drupal\Driver\Fields\FieldHandlerInterface;

/**
 * Base class for field handlers in Drupal 8.
 */
abstract class AbstractHandler implements FieldHandlerInterface {
  /**
   * Field storage definition.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldInfo = NULL;

  /**
   * Field configuration definition.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $fieldConfig = NULL;

  /**
   * Constructs an AbstractHandler object.
   *
   * @param \stdClass $entity
   *   The simulated entity object containing field information.
   * @param string $entity_type
   *   The entity type.
   * @param string $field_name
   *   The field name.
   *
   * @throws \Exception
   *   Thrown when the given field name does not exist on the entity.
   */
  public function __construct(\stdClass $entity, $entity_type, $field_name) {
    $entity_manager = \Drupal::entityManager();
    $fields = $entity_manager->getFieldStorageDefinitions($entity_type);
    $this->fieldInfo = $fields[$field_name];

    $bundle_key = $entity_manager->getDefinition($entity_type)->getKey('bundle');
    $bundle = !empty($entity->$bundle_key) ? $entity->$bundle_key : $entity_type;

    $fields = $entity_manager->getFieldDefinitions($entity_type, $bundle);
    if (empty($fields[$field_name])) {
      throw new \Exception(sprintf('The field "%s" does not exist on entity type "%s".', $field_name, $entity_type));
    }
    $this->fieldConfig = $fields[$field_name];
  }

}
