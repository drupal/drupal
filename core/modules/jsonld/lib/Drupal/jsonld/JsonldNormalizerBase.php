<?php

/**
 * @file
 * Definition of Drupal\jsonld\JsonldNormalizerBase.
 */

namespace Drupal\jsonld;

use Drupal\Core\Entity\EntityNG;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;

/**
 * Provide a base class for JSON-LD Normalizers.
 */
abstract class JsonldNormalizerBase extends SerializerAwareNormalizer implements NormalizerInterface {

  /**
   * The formats that this Normalizer supports.
   *
   * @var array
   */
  static protected $format = array('jsonld', 'drupal_jsonld');

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize()
   */
  public function supportsNormalization($data, $format = NULL) {
    return is_object($data) && in_array($format, static::$format);
  }

}
