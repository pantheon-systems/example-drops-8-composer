<?php

namespace Drupal\Driver\Fields\Drupal7;

/**
 * ListBoolean field handler for Drupal 7.
 */
class ListBooleanHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $return = array();
    $allowed_values = $this->fieldInfo['settings']['allowed_values'];
    // If values are blank then use keys as value.
    foreach ($allowed_values as $key => $value) {
      if ($value == '') {
        $allowed_values[$key] = $key;
      }
    }
    $allowed_values = array_flip($allowed_values);
    foreach ($values as $value) {
      $return[$this->language][] = array('value' => $allowed_values[$value]);
    }
    return $return;
  }

}
