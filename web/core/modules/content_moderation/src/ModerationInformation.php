<?php

namespace Drupal\content_moderation;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * General service for moderation-related questions about Entity API.
 */
class ModerationInformation implements ModerationInformationInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The bundle information service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Creates a new ModerationInformation instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The bundle information service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfo = $bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public function isModeratedEntity(EntityInterface $entity) {
    if (!$entity instanceof ContentEntityInterface) {
      return FALSE;
    }

    return $this->shouldModerateEntitiesOfBundle($entity->getEntityType(), $entity->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function canModerateEntitiesOfEntityType(EntityTypeInterface $entity_type) {
    return $entity_type->hasHandlerClass('moderation');
  }

  /**
   * {@inheritdoc}
   */
  public function shouldModerateEntitiesOfBundle(EntityTypeInterface $entity_type, $bundle) {
    if ($this->canModerateEntitiesOfEntityType($entity_type)) {
      $bundles = $this->bundleInfo->getBundleInfo($entity_type->id());
      return isset($bundles[$bundle]['workflow']);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestRevision($entity_type_id, $entity_id) {
    if ($latest_revision_id = $this->getLatestRevisionId($entity_type_id, $entity_id)) {
      return $this->entityTypeManager->getStorage($entity_type_id)->loadRevision($latest_revision_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestRevisionId($entity_type_id, $entity_id) {
    if ($storage = $this->entityTypeManager->getStorage($entity_type_id)) {
      $revision_ids = $storage->getQuery()
        ->allRevisions()
        ->condition($this->entityTypeManager->getDefinition($entity_type_id)->getKey('id'), $entity_id)
        ->sort($this->entityTypeManager->getDefinition($entity_type_id)->getKey('revision'), 'DESC')
        ->range(0, 1)
        ->execute();
      if ($revision_ids) {
        return array_keys($revision_ids)[0];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultRevisionId($entity_type_id, $entity_id) {
    if ($storage = $this->entityTypeManager->getStorage($entity_type_id)) {
      $revision_ids = $storage->getQuery()
        ->condition($this->entityTypeManager->getDefinition($entity_type_id)->getKey('id'), $entity_id)
        ->sort($this->entityTypeManager->getDefinition($entity_type_id)->getKey('revision'), 'DESC')
        ->range(0, 1)
        ->execute();
      if ($revision_ids) {
        return array_keys($revision_ids)[0];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isLatestRevision(ContentEntityInterface $entity) {
    return $entity->getRevisionId() == $this->getLatestRevisionId($entity->getEntityTypeId(), $entity->id());
  }

  /**
   * {@inheritdoc}
   */
  public function hasForwardRevision(ContentEntityInterface $entity) {
    return $this->isModeratedEntity($entity)
      && !($this->getLatestRevisionId($entity->getEntityTypeId(), $entity->id()) == $this->getDefaultRevisionId($entity->getEntityTypeId(), $entity->id()));
  }

  /**
   * {@inheritdoc}
   */
  public function isLiveRevision(ContentEntityInterface $entity) {
    $workflow = $this->getWorkflowForEntity($entity);
    return $this->isLatestRevision($entity)
      && $entity->isDefaultRevision()
      && $entity->moderation_state->value
      && $workflow->getState($entity->moderation_state->value)->isPublishedState();
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflowForEntity(ContentEntityInterface $entity) {
    $bundles = $this->bundleInfo->getBundleInfo($entity->getEntityTypeId());
    if (isset($bundles[$entity->bundle()]['workflow'])) {
      return $this->entityTypeManager->getStorage('workflow')->load($bundles[$entity->bundle()]['workflow']);
    };
    return NULL;
  }

}
