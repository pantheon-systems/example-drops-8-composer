<?php

namespace Drupal\migrate_drupal\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a cckfield plugin annotation object.
 *
 * cckfield plugins are variously responsible for handling the migration of
 * CCK fields from Drupal 6 to Drupal 8, and Field API fields from Drupal 7
 * to Drupal 8. They are allowed to alter CCK-related migrations when migrations
 * are being generated, and can compute destination field types for individual
 * fields during the actual migration process.
 *
 * Plugin Namespace: Plugin\migrate\cckfield
 *
 * @Annotation
 */
class MigrateCckField extends Plugin {

  /**
   * @inheritdoc
   */
  public function __construct($values) {
    parent::__construct($values);
    // Provide default value for core property, in case it's missing.
    if (empty($this->definition['core'])) {
      $this->definition['core'] = [6];
    }
  }

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * Map of D6 and D7 field types to D8 field type plugin IDs.
   *
   * @var string[]
   */
  public $type_map = [];

  /**
   * The Drupal core version(s) this plugin applies to.
   *
   * @var int[]
   */
  public $core = [];

}
