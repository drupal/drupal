<?php

/**
 * @file
 * Definition of Drupal\jsonld\JsonldFieldItemNormalizer.
 */

namespace Drupal\jsonld;

use Drupal\Core\Entity\Field\FieldItemInterface;
use Drupal\jsonld\JsonldNormalizerBase;
use Symfony\Component\Serializer\Exception\RuntimeException;

/**
 * Converts the Drupal entity object structure to JSON-LD array structure.
 */
class JsonldFieldItemNormalizer extends JsonldNormalizerBase {

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize()
   */
  public function normalize($object, $format = NULL) {
    return $object->getPropertyValues();
  }

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface::supportsNormalization()
   */
  public function supportsNormalization($data, $format = NULL) {
    return parent::supportsNormalization($data, $format) && ($data instanceof FieldItemInterface);
  }

}
