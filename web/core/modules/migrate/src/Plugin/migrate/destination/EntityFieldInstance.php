<?php

namespace Drupal\migrate\Plugin\migrate\destination;

/**
 * Provides entity field instance plugin.
 *
 * @MigrateDestination(
 *   id = "entity:field_config"
 * )
 */
class EntityFieldInstance extends EntityConfigBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['entity_type']['type'] = 'string';
    $ids['bundle']['type'] = 'string';
    $ids['field_name']['type'] = 'string';
    if ($this->isTranslationDestination()) {
      $ids['langcode']['type'] = 'string';
    }
    return $ids;
  }

}
