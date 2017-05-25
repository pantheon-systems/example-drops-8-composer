<?php

namespace Drupal\Driver\Fields\Drupal7;

/**
 * Taxonomy term reference field handler for Drupal 7.
 */
class TaxonomyTermReferenceHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $return = array();
    foreach ($values as $name) {
      $terms = taxonomy_get_term_by_name($name, $this->getVocab());
      if (!$terms) {
        throw new \Exception(sprintf("No term '%s' exists.", $name));
      }
      $return[$this->language][] = array('tid' => array_shift($terms)->tid);
    }
    return $return;
  }

  /**
   * Attempt to determine the vocabulary for which the field is configured.
   *
   * @return mixed
   *   Returns a string containing the vocabulary in which the term must be
   *   found or NULL if unable to determine.
   */
  protected function getVocab() {
    if (!empty($this->field_info['settings']['allowed_values'][0]['vocabulary'])) {
      return $this->field_info['settings']['allowed_values'][0]['vocabulary'];
    }
  }

}
