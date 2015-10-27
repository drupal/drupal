<?php

/**
 * @file
 * Contains \Drupal\serialization\Normalizer\EntityReferenceFieldItemNormalizer.
 */

namespace Drupal\serialization\Normalizer;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

/**
 * Adds the file URI to embedded file entities.
 */
class EntityReferenceFieldItemNormalizer extends ComplexDataNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = EntityReferenceItem::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    $values = parent::normalize($field_item, $format, $context);

    // Add a 'url' value if there is a reference and a canonical URL. Hard code
    // 'canonical' here as config entities override the default $rel parameter
    // value to 'edit-form.
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    if (($entity = $field_item->get('entity')->getValue()) && ($url = $entity->url('canonical'))) {
      $values['url'] = $url;
    }

    return $values;
  }

}
