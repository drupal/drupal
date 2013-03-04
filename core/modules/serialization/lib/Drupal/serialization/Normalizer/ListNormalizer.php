<?php

/**
 * @file
 * Contains \Drupal\serialization\Normalizer\ListNormalizer.
 */

namespace Drupal\serialization\Normalizer;

use Symfony\Component\Serializer\Exception\RuntimeException;

/**
 * Converts list objects to arrays.
 *
 * Ordinarily, this would be handled automatically by Serializer, but since
 * there is a TypedDataNormalizer and the Field class extends TypedData, any
 * Field will be handled by that Normalizer instead of being traversed. This
 * class ensures that TypedData classes that also implement ListInterface are
 * traversed instead of simply returning getValue().
 */
class ListNormalizer extends NormalizerBase {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\TypedData\ListInterface';

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize().
   */
  public function normalize($object, $format = NULL, array $context = array()) {
    $attributes = array();
    foreach ($object as $fieldItem) {
      $attributes[] = $this->serializer->normalize($fieldItem, $format);
    }
    return $attributes;
  }

}
