<?php

namespace Drupal\block_content;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\RedirectDestinationTrait;

/**
 * Defines a class to build a listing of custom block entities.
 *
 * @see \Drupal\block_content\Entity\BlockContent
 */
class BlockContentListBuilder extends EntityListBuilder {

  use RedirectDestinationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Block description');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    if (isset($operations['edit'])) {
      $operations['edit']['query']['destination'] = $this->getRedirectDestination()->get();
    }
    return $operations;
  }

}
