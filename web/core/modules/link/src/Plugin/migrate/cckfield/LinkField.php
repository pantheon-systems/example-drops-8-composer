<?php

namespace Drupal\link\Plugin\migrate\cckfield;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\cckfield\CckFieldPluginBase;

/**
 * @MigrateCckField(
 *   id = "link",
 *   core = {6},
 *   type_map = {
 *     "link_field" = "link"
 *   }
 * )
 */
class LinkField extends CckFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    // See d6_field_formatter_settings.yml and CckFieldPluginBase
    // processFieldFormatter().
    return [
      'default' => 'link',
      'plain' => 'link',
      'absolute' => 'link',
      'title_plain' => 'link',
      'url' => 'link',
      'short' => 'link',
      'label' => 'link',
      'separate' => 'link_separate',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processCckFieldValues(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'd6_cck_link',
      'source' => $field_name,
    ];
    $migration->mergeProcessOfProperty($field_name, $process);
  }

}
