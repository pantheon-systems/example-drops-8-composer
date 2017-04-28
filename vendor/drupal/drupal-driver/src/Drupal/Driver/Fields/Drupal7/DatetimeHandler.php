<?php

namespace Drupal\Driver\Fields\Drupal7;

/**
 * Datetime field handler for Drupal 7.
 */
class DatetimeHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $return = array();
    if (isset($this->fieldInfo['columns']['value2'])) {
      foreach ($values as $value) {
        $return[$this->language][] = array(
          'value' => $value[0],
          'value2' => $value[1],
        );
      }
    }
    else {
      foreach ($values as $value) {
        $return[$this->language][] = array('value' => $value);
      }
    }
    return $return;
  }

}
