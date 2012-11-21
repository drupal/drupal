<?php

/**
 * @file
 * Definition of Drupal\jsonld\JsonldEntityNormalizer.
 */

namespace Drupal\jsonld;

use Drupal\Core\Entity\EntityNG;
use Drupal\jsonld\JsonldNormalizerBase;
use Symfony\Component\Serializer\Exception\RuntimeException;

/**
 * Converts the Drupal entity object structure to JSON-LD array structure.
 */
class JsonldEntityNormalizer extends JsonldNormalizerBase {

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize()
   */
  public function normalize($entity, $format = NULL) {
    $entityWrapper = new JsonldEntityWrapper($entity, $format, $this->serializer);

    $attributes = $entityWrapper->getProperties();
    $attributes = array('@id' => $entityWrapper->getId()) + $attributes;
    return $attributes;
  }

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface::supportsNormalization()
   */
  public function supportsNormalization($data, $format = NULL) {
    // @todo Switch to EntityInterface once all entity types are converted to
    // EntityNG.
    return parent::supportsNormalization($data, $format) && ($data instanceof EntityNG);
  }

}
