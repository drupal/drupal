<?php

namespace Drupal\serialization\Normalizer;

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
  public function normalize($object, $format = NULL, array $context = []) {
    $context += [
      'account' => NULL,
    ];

    $attributes = [];
    foreach ($object as $name => $field) {
      if ($field->access('view', $context['account'])) {
        $attributes[$name] = $this->serializer->normalize($field, $format, $context);
      }
    }

    return $attributes;
  }

}
