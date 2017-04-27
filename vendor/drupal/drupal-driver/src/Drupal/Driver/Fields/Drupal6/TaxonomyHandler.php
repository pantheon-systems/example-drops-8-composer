<?php

namespace Drupal\Driver\Fields\Drupal6;

use Drupal\Driver\Fields\FieldHandlerInterface;

/**
 * Provides a custom field handler to make it easier to include taxonomy terms.
 */
class TaxonomyHandler implements FieldHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $result = array();
    $values = (array) $values;
    foreach ($values as $entry) {
      $terms = explode(',', $entry);
      foreach ($terms as $term) {
        // Try to split things out in order to find optional specified vocabs.
        $term_name_or_tid = '';
        $parts = explode(':', $term);
        if (count($parts) == 1) {
          $term_name_or_tid = $term;
        }
        elseif (count($parts) == 2) {
          $term_name_or_tid = $term;
        }
        if ($term_list = taxonomy_get_term_by_name($term_name_or_tid)) {
          $term = reset($term_list);
          $result[] = $term;
        }
      }
    }

    return $result;
  }

}
