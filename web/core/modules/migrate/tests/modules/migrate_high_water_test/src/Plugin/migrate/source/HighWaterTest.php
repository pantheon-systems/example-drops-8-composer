<?php

namespace Drupal\migrate_sql_test\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for migration high water tests.
 *
 * @MigrateSource(
 *   id = "high_water_test"
 * )
 */
class HighWaterTest extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this
      ->select('high_water_node', 'm')
      ->fields('m', array_keys($this->fields()));
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Id'),
      'title' => $this->t('Title'),
      'changed' => $this->t('Changed'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
      ],
    ];
  }

}
