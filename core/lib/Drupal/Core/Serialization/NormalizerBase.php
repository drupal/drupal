<?php

/**
 * @file
 * Contains Drupal\Core\Serialization\NormalizerBase.
 */

namespace Drupal\Core\Serialization;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;

/**
 * Base class for Normalizers.
 */
abstract class NormalizerBase extends SerializerAwareNormalizer implements NormalizerInterface {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected static $supportedInterfaceOrClass;

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface::supportsNormalization().
   */
  public function supportsNormalization($data, $format = NULL) {
    return is_object($data) && ($data instanceof static::$supportedInterfaceOrClass);
  }

}
