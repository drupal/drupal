<?php

/**
 * @file
 * Definition of Drupal\jsonld\JsonldNormalizerBase.
 */

namespace Drupal\jsonld;

use ReflectionClass;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;

/**
 * Provide a base class for JSON-LD Normalizers.
 */
abstract class JsonldNormalizerBase extends SerializerAwareNormalizer implements NormalizerInterface {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected static $supportedInterfaceOrClass;

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
    return is_object($data) && in_array($format, static::$format) && ($data instanceof static::$supportedInterfaceOrClass);
  }

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\DenormalizerInterface::supportsDenormalization()
   *
   * This class doesn't implement DenormalizerInterface, but most of its child
   * classes do, so this method is implemented at this level to reduce code
   * duplication.
   */
  public function supportsDenormalization($data, $type, $format = NULL) {
    $reflection = new ReflectionClass($type);
    return in_array($format, static::$format) && $reflection->implementsInterface(static::$supportedInterfaceOrClass);
  }
}
