<?php

namespace Drupal\Driver\Fields\Drupal7;

/**
 * Default field handler for Drupal 7.
 */
class DefaultHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $return = array();
    foreach ($values as $value) {
      // Use the column name 'value' by default if the value is not an array.
      if (!is_array($value)) {
        $value = array('value' => $value);
      }
      $return[$this->language][] = $value;
    }
    return $return;
  }

}
