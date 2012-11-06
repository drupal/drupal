<?php

/**
 * @file
 * Definition of Drupal\jsonld\JsonldNormalizer.
 */

namespace Drupal\jsonld;

use Drupal\Core\Entity\EntityNG;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Converts the Drupal entity object structure to JSON-LD array structure.
 */
class JsonldNormalizer implements NormalizerInterface {

  /**
   * The format that this Normalizer supports.
   *
   * @var string
   */
  static protected $format = 'jsonld';

  /**
   * The class to use for the entity wrapper object.
   *
   * @var string
   */
  protected $entityWrapperClass = 'Drupal\jsonld\JsonldEntityWrapper';

  /**
   * Normalizes an object into a set of arrays/scalars.
   *
   * @param object $object
   *   Object to normalize.
   * @param string $format
   *   Format the normalization result will be encoded as.
   *
   * @return array
   *   An array containing the properties of the entity and JSON-LD specific
   *   attributes such as '@context' and '@id'.
   */
  public function normalize($object, $format = NULL) {
    $entityWrapper = new $this->entityWrapperClass($object);

    $attributes = $entityWrapper->getProperties();
    $attributes = array('@id' => $entityWrapper->getId()) + $attributes;
    return $attributes;
  }

  /**
   * Checks whether the data and format are supported by this normalizer.
   *
   * @param mixed  $data
   *   Data to normalize.
   * @param string $format
   *   Format the normalization result will be encoded as.
   *
   * @return bool
   *   Returns TRUE if the normalizer can handle the request.
   */
  public function supportsNormalization($data, $format = NULL) {
    // If this is an Entity object and the request is for JSON-LD.
    return is_object($data) && ($data instanceof EntityNG) && static::$format === $format;
  }

}
