<?php

namespace Drupal\Driver\Fields\Drupal8;

/**
 * Datetime field handler for Drupal 8.
 */
class DatetimeHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    foreach ($values as $key => $value) {
      $values[$key] = str_replace(' ', 'T', $value);
    }
    return $values;
  }

}
