<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\TypedData\TypedDataInternalPropertiesHelper;

/**
 * Normalizes/denormalizes Drupal content entities into an array structure.
 */
class ContentEntityNormalizer extends EntityNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = ['Drupal\Core\Entity\ContentEntityInterface'];

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    $context += [
      'account' => NULL,
    ];

    $attributes = [];
<<<<<<< HEAD
    /** @var \Drupal\Core\Entity\Entity $entity */
    foreach (TypedDataInternalPropertiesHelper::getNonInternalProperties($entity->getTypedData()) as $name => $field_items) {
=======
    foreach ($entity as $name => $field_items) {
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
      if ($field_items->access('view', $context['account'])) {
        $attributes[$name] = $this->serializer->normalize($field_items, $format, $context);
      }
    }

    return $attributes;
  }

}
