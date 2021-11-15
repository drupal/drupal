<?php

namespace Drupal\hal\Normalizer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\hal\LinkManager\LinkManagerInterface;
use Drupal\serialization\EntityResolver\EntityResolverInterface;
use Drupal\serialization\EntityResolver\UuidReferenceInterface;
use Drupal\serialization\Normalizer\EntityReferenceFieldItemNormalizerTrait;

/**
 * Converts the Drupal entity reference item object to HAL array structure.
 */
class EntityReferenceItemNormalizer extends FieldItemNormalizer implements UuidReferenceInterface {

  use EntityReferenceFieldItemNormalizerTrait;

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = EntityReferenceItem::class;

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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an EntityReferenceItemNormalizer object.
   *
   * @param \Drupal\hal\LinkManager\LinkManagerInterface $link_manager
   *   The hypermedia link manager.
   * @param \Drupal\serialization\EntityResolver\EntityResolverInterface $entity_Resolver
   *   The entity resolver.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface|null $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(LinkManagerInterface $link_manager, EntityResolverInterface $entity_Resolver, EntityTypeManagerInterface $entity_type_manager = NULL) {
    $this->linkManager = $link_manager;
    $this->entityResolver = $entity_Resolver;
    $this->entityTypeManager = $entity_type_manager ?: \Drupal::service('entity_type.manager');
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    // If this is not a fieldable entity, let the parent implementation handle
    // it, only fieldable entities are supported as embedded resources.
    if (!$this->targetEntityIsFieldable($field_item)) {
      return parent::normalize($field_item, $format, $context);
    }

    /** @var \Drupal\Core\Field\FieldItemInterface $field_item */
    $target_entity = $field_item->get('entity')->getValue();

    // If the parent entity passed in a langcode, unset it before normalizing
    // the target entity. Otherwise, untranslatable fields of the target entity
    // will include the langcode.
    $langcode = $context['langcode'] ?? NULL;
    unset($context['langcode']);
    $context['included_fields'] = ['uuid'];

    // Normalize the target entity.
    $embedded = $this->serializer->normalize($target_entity, $format, $context);
    // @todo https://www.drupal.org/project/drupal/issues/3110815 $embedded will
    //   be NULL if the target entity does not exist. Use null coalescence
    //   operator to preserve behavior in PHP 7.4.
    $link = $embedded['_links']['self'] ?? NULL;
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
   * Checks whether the referenced entity is of a fieldable entity type.
   *
   * @param \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item
   *   The reference field item whose target entity needs to be checked.
   *
   * @return bool
   *   TRUE when the referenced entity is of a fieldable entity type.
   */
  protected function targetEntityIsFieldable(EntityReferenceItem $item) {
    $target_entity = $item->get('entity')->getValue();

    if ($target_entity !== NULL) {
      return $target_entity instanceof FieldableEntityInterface;
    }

    $referencing_entity = $item->getEntity();
    $target_entity_type_id = $item->getFieldDefinition()->getSetting('target_type');

    // If the entity type is the same as the parent, we can check that. This is
    // just a shortcut to avoid getting the entity type definition and checking
    // the class.
    if ($target_entity_type_id === $referencing_entity->getEntityTypeId()) {
      return $referencing_entity instanceof FieldableEntityInterface;
    }

    // Otherwise, we need to get the class for the type.
    $target_entity_type = $this->entityTypeManager->getDefinition($target_entity_type_id);
    $target_entity_type_class = $target_entity_type->getClass();

    return is_a($target_entity_type_class, FieldableEntityInterface::class, TRUE);
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
      return ['target_id' => $id] + array_intersect_key($data, $field_item->getProperties());
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function normalizedFieldValues(FieldItemInterface $field_item, $format, array $context) {
    // Normalize root reference values here so we don't need to deal with hal's
    // nested data structure for field items. This will be called from
    // \Drupal\hal\Normalizer\FieldItemNormalizer::normalize. Which will only
    // be called from this class for entities that are not fieldable.
    $normalized = parent::normalizedFieldValues($field_item, $format, $context);

    $this->normalizeRootReferenceValue($normalized, $field_item);

    return $normalized;
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
