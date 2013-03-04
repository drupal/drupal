<?php

/**
 * @file
 * Contains \Drupal\serialization\Normalizer\NormalizerBase.
 */

namespace Drupal\serialization\Normalizer;

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
  protected $supportedInterfaceOrClass;

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface::supportsNormalization().
   */
  public function supportsNormalization($data, $format = NULL) {
    return is_object($data) && ($data instanceof $this->supportedInterfaceOrClass);
  }

}
