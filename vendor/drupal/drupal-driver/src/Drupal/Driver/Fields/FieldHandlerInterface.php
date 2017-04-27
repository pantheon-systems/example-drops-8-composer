<?php

namespace Drupal\Driver\Fields;

/**
 * Interface for handling fields.
 *
 * Saving fields on entities is handled differently depending on the Drupal
 * version. This interface translates abstract field data into the format that
 * is expected by the different storage handlers.
 */
interface FieldHandlerInterface {

  /**
   * Expand abstract field values so they can be saved on the entity.
   *
   * This method takes care of the different ways that field data is saved on
   * entities in different versions of Drupal.
   *
   * @param mixed $values
   *   A single value or an array of field values to save on the entity.
   *
   * @return array
   *   An array of field values in the format expected by the entity storage
   *   handlers in the driver's version of Drupal.
   */
  public function expand($values);

}
