<?php

namespace Drupal\Driver\Fields\Drupal7;

/**
 * Link field handler for Drupal 7.
 */
class LinkFieldHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $return = array();
    foreach ($values as $value) {
      $return[$this->language][] = array(
        'title' => $value[0],
        'url' => $value[1],
      );
    }
    return $return;
  }

}
