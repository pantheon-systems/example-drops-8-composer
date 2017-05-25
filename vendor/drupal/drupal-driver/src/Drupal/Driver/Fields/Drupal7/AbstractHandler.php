<?php

namespace Drupal\Driver\Fields\Drupal7;

use Drupal\Driver\Fields\FieldHandlerInterface;

/**
 * Base class for field handlers in Drupal 7.
 */
abstract class AbstractHandler implements FieldHandlerInterface {

  /**
   * The entity language.
   *
   * @var string
   */
  protected $language = NULL;

  /**
   * The simulated entity.
   *
   * @var \stdClass
   */
  protected $entity = NULL;

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType = NULL;

  /**
   * The field name.
   *
   * @var string
   */
  protected $fieldName = NULL;

  /**
   * The field array, as returned by field_read_fields().
   *
   * @var array
   */
  protected $fieldInfo = array();

  /**
   * Constructs an AbstractHandler object.
   *
   * @param \stdClass $entity
   *   The simulated entity object containing field information.
   * @param string $entity_type
   *   The entity type.
   * @param string $field_name
   *   The field name.
   */
  public function __construct(\stdClass $entity, $entity_type, $field_name) {
    $this->entity = $entity;
    $this->entityType = $entity_type;
    $this->fieldName = $field_name;
    $this->fieldInfo = $this->getFieldInfo();
    $this->language = $this->getEntityLanguage();
  }

  /**
   * Magic caller.
   */
  public function __call($method, $args) {
    if ($method == 'expand') {
      $args['values'] = (array) $args['values'];
    }
    return call_user_func_array(array($this, $method), $args);
  }

  /**
   * Returns field information.
   *
   * @return array
   *   The field array, as returned by field_read_fields().
   */
  public function getFieldInfo() {
    return field_info_field($this->fieldName);
  }

  /**
   * Returns the entity language.
   *
   * @return string
   *   The entity language.
   */
  public function getEntityLanguage() {
    if (field_is_translatable($this->entityType, $this->fieldInfo)) {
      return entity_language($this->entityType, $this->entity);
    }
    return LANGUAGE_NONE;
  }

}
