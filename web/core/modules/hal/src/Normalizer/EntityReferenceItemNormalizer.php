<?php

namespace Drupal\hal\Normalizer;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\hal\LinkManager\LinkManagerInterface;
use Drupal\serialization\EntityResolver\EntityResolverInterface;
use Drupal\serialization\EntityResolver\UuidReferenceInterface;

/**
 * Converts the Drupal entity reference item object to HAL array structure.
 */
class EntityReferenceItemNormalizer extends FieldItemNormalizer implements UuidReferenceInterface {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem';

  /**
   * The hypermedia link manager.
   *
   * @var \Drupal\hal\LinkManager\LinkManagerInterface
   */
  protected $linkManager;

  /**
   * The entity resolver.
   *
   * @var \Drupal\serialization\EntityResolver\EntityResolverInterface
   */
  protected $entityResolver;

  /**
   * Constructs an EntityReferenceItemNormalizer object.
   *
   * @param \Drupal\hal\LinkManager\LinkManagerInterface $link_manager
   *   The hypermedia link manager.
   * @param \Drupal\serialization\EntityResolver\EntityResolverInterface $entity_Resolver
   *   The entity resolver.
   */
  public function __construct(LinkManagerInterface $link_manager, EntityResolverInterface $entity_Resolver) {
    $this->linkManager = $link_manager;
    $this->entityResolver = $entity_Resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    /** @var $field_item \Drupal\Core\Field\FieldItemInterface */
    $target_entity = $field_item->get('entity')->getValue();

    // If this is not a content entity, let the parent implementation handle it,
    // only content entities are supported as embedded resources.
    if (!($target_entity instanceof FieldableEntityInterface)) {
      return parent::normalize($field_item, $format, $context);
    }

    // If the parent entity passed in a langcode, unset it before normalizing
    // the target entity. Otherwise, untranslatable fields of the target entity
    // will include the langcode.
    $langcode = isset($context['langcode']) ? $context['langcode'] : NULL;
    unset($context['langcode']);
    $context['included_fields'] = ['uuid'];

    // Normalize the target entity.
    $embedded = $this->serializer->normalize($target_entity, $format, $context);
    $link = $embedded['_links']['self'];
    // If the field is translatable, add the langcode to the link relation
    // object. This does not indicate the language of the target entity.
    if ($langcode) {
      $embedded['lang'] = $link['lang'] = $langcode;
    }

    // The returned structure will be recursively merged into the normalized
    // entity so that the items are properly added to the _links and _embedded
    // objects.
    $field_name = $field_item->getParent()->getName();
    $entity = $field_item->getEntity();
    $field_uri = $this->linkManager->getRelationUri($entity->getEntityTypeId(), $entity->bundle(), $field_name, $context);
    return [
      '_links' => [
        $field_uri => [$link],
      ],
      '_embedded' => [
        $field_uri => [$embedded],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function constructValue($data, $context) {
    $field_item = $context['target_instance'];
    $field_definition = $field_item->getFieldDefinition();
    $target_type = $field_definition->getSetting('target_type');
    $id = $this->entityResolver->resolve($this, $data, $target_type);
    if (isset($id)) {
      return ['target_id' => $id];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getUuid($data) {
    if (isset($data['uuid'])) {
      $uuid = $data['uuid'];
      // The value may be a nested array like $uuid[0]['value'].
      if (is_array($uuid) && isset($uuid[0]['value'])) {
        $uuid = $uuid[0]['value'];
      }
      return $uuid;
    }
  }

}
