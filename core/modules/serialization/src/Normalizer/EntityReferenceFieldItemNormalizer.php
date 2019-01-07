<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Adds the file URI to embedded file entities.
 */
class EntityReferenceFieldItemNormalizer extends FieldItemNormalizer {

  use EntityReferenceFieldItemNormalizerTrait;

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = EntityReferenceItem::class;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a EntityReferenceFieldItemNormalizer object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(EntityRepositoryInterface $entity_repository) {
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    $values = parent::normalize($field_item, $format, $context);

    $this->normalizeRootReferenceValue($values, $field_item);

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    if ($entity = $field_item->get('entity')->getValue()) {
      $values['target_type'] = $entity->getEntityTypeId();
      // Add the target entity UUID to the normalized output values.
      $values['target_uuid'] = $entity->uuid();

      // Add a 'url' value if there is a reference and a canonical URL. Hard
      // code 'canonical' here as config entities override the default $rel
      // parameter value to 'edit-form.
      if ($entity->hasLinkTemplate('canonical') && !$entity->isNew() && $url = $entity->toUrl('canonical')->toString(TRUE)) {
        $this->addCacheableDependency($context, $url);
        $values['url'] = $url->getGeneratedUrl();
      }
      // @todo Remove in https://www.drupal.org/project/drupal/issues/2925520
      // @see \Drupal\hal\Normalizer\FileEntityNormalizer
      elseif ($entity->getEntityTypeId() === 'file') {
        $values['url'] = file_create_url($entity->getFileUri());
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  protected function constructValue($data, $context) {
    if (isset($data['target_uuid'])) {
      /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $field_item */
      $field_item = $context['target_instance'];
      if (empty($data['target_uuid'])) {
        throw new InvalidArgumentException(sprintf('If provided "target_uuid" cannot be empty for field "%s".', $data['target_type'], $data['target_uuid'], $field_item->getName()));
      }
      $target_type = $field_item->getFieldDefinition()->getSetting('target_type');
      if (!empty($data['target_type']) && $target_type !== $data['target_type']) {
        throw new UnexpectedValueException(sprintf('The field "%s" property "target_type" must be set to "%s" or omitted.', $field_item->getFieldDefinition()->getName(), $target_type));
      }
      if ($entity = $this->entityRepository->loadEntityByUuid($target_type, $data['target_uuid'])) {
        return ['target_id' => $entity->id()] + array_intersect_key($data, $field_item->getProperties());
      }
      else {
        // Unable to load entity by uuid.
        throw new InvalidArgumentException(sprintf('No "%s" entity found with UUID "%s" for field "%s".', $data['target_type'], $data['target_uuid'], $field_item->getName()));
      }
    }
    return parent::constructValue($data, $context);
  }

}
