<?php

/**
 * @file
 * Definition of Drupal\jsonld\JsonldFieldItemNormalizer.
 */

namespace Drupal\jsonld;

use Drupal\Core\Entity\Field\FieldItemInterface;
use Drupal\jsonld\JsonldNormalizerBase;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Converts the Drupal entity object structure to JSON-LD array structure.
 */
class JsonldFieldItemNormalizer extends JsonldNormalizerBase implements DenormalizerInterface {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected static $supportedInterfaceOrClass = 'Drupal\Core\Entity\Field\FieldItemInterface';

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize()
   */
  public function normalize($object, $format = NULL) {
    return $object->getPropertyValues();
  }

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\DenormalizerInterface::denormalize()
   */
  public function denormalize($data, $class, $format = null) {
    // For most fields, the field items array should simply be returned as is.
    return $data;
  }

}
