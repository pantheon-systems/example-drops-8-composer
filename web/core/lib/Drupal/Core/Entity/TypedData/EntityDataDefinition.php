<?php

namespace Drupal\Core\Entity\TypedData;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;

/**
 * A typed data definition class for describing entities.
 */
class EntityDataDefinition extends ComplexDataDefinitionBase implements EntityDataDefinitionInterface {

  /**
   * Creates a new entity definition.
   *
   * @param string $entity_type_id
   *   (optional) The ID of the entity type, or NULL if the entity type is
   *   unknown. Defaults to NULL.
   *
   * @return static
   */
  public static function create($entity_type_id = NULL) {
    $definition = new static([]);
    // Set the passed entity type.
    if (isset($entity_type_id)) {
      $definition->setEntityTypeId($entity_type_id);
    }
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromDataType($data_type) {
    $parts = explode(':', $data_type);
    if ($parts[0] != 'entity') {
      throw new \InvalidArgumentException('Data type must be in the form of "entity:ENTITY_TYPE:BUNDLE."');
    }
    $definition = static::create();
    // Set the passed entity type and bundle.
    if (isset($parts[1])) {
      $definition->setEntityTypeId($parts[1]);
    }
    if (isset($parts[2])) {
      $definition->setBundles([$parts[2]]);
    }
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      if ($entity_type_id = $this->getEntityTypeId()) {
        // Return an empty array for entities that are not content entities.
        $entity_type_class = \Drupal::entityManager()->getDefinition($entity_type_id)->getClass();
        if (!in_array('Drupal\Core\Entity\FieldableEntityInterface', class_implements($entity_type_class))) {
          $this->propertyDefinitions = [];
        }
        else {
          // @todo: Add support for handling multiple bundles.
          // See https://www.drupal.org/node/2169813.
          $bundles = $this->getBundles();
          if (is_array($bundles) && count($bundles) == 1) {
            $this->propertyDefinitions = \Drupal::entityManager()->getFieldDefinitions($entity_type_id, reset($bundles));
          }
          else {
            $this->propertyDefinitions = \Drupal::entityManager()->getBaseFieldDefinitions($entity_type_id);
          }
        }
      }
      else {
        // No entity type given.
        $this->propertyDefinitions = [];
      }
    }
    return $this->propertyDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataType() {
    $type = 'entity';
    if ($entity_type = $this->getEntityTypeId()) {
      $type .= ':' . $entity_type;
      // Append the bundle only if we know it for sure and it is not the default
      // bundle.
      if (($bundles = $this->getBundles()) && count($bundles) == 1) {
        $bundle = reset($bundles);
        if ($bundle != $entity_type) {
          $type .= ':' . $bundle;
        }
      }
    }
    return $type;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return isset($this->definition['constraints']['EntityType']) ? $this->definition['constraints']['EntityType'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityTypeId($entity_type_id) {
    return $this->addConstraint('EntityType', $entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getBundles() {
    $bundle = isset($this->definition['constraints']['Bundle']) ? $this->definition['constraints']['Bundle'] : NULL;
    return is_string($bundle) ? [$bundle] : $bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function setBundles(array $bundles = NULL) {
    if (isset($bundles)) {
      $this->addConstraint('Bundle', $bundles);
    }
    else {
      // Remove the constraint.
      unset($this->definition['constraints']['Bundle']);
    }
    return $this;
  }

}
