<?php

/**
 * @file
 * Contains \Drupal\serialization\Normalizer\ContentEntityNormalizer.
 */

namespace Drupal\serialization\Normalizer;

/**
 * Normalizes/denormalizes Drupal content entities into an array structure.
 */
class ContentEntityNormalizer extends EntityNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var array
   */
  protected $supportedInterfaceOrClass = ['Drupal\Core\Entity\ContentEntityInterface'];

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = array()) {
    $context += array(
      'account' => NULL,
    );

    $attributes = [];
    foreach ($object as $name => $field) {
      if ($field->access('view', $context['account'])) {
        $attributes[$name] = $this->serializer->normalize($field, $format, $context);
      }
    }

    return $attributes;
  }

}
