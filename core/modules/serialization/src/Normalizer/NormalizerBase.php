<?php

namespace Drupal\serialization\Normalizer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;

/**
 * Base class for Normalizers.
 */
abstract class NormalizerBase extends SerializerAwareNormalizer implements NormalizerInterface {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string|array
   */
  protected $supportedInterfaceOrClass;

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    // If we aren't dealing with an object or the format is not supported return
    // now.
    if (!is_object($data) || !$this->checkFormat($format)) {
      return FALSE;
    }

    $supported = (array) $this->supportedInterfaceOrClass;

    return (bool) array_filter($supported, function($name) use ($data) {
      return $data instanceof $name;
    });
  }

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\DenormalizerInterface::supportsDenormalization()
   *
   * This class doesn't implement DenormalizerInterface, but most of its child
   * classes do, so this method is implemented at this level to reduce code
   * duplication.
   */
  public function supportsDenormalization($data, $type, $format = NULL) {
    // If the format is not supported return now.
    if (!$this->checkFormat($format)) {
      return FALSE;
    }

    $supported = (array) $this->supportedInterfaceOrClass;

    $subclass_check = function($name) use ($type) {
      return (class_exists($name) || interface_exists($name)) && is_subclass_of($type, $name, TRUE);
    };

    return in_array($type, $supported) || array_filter($supported, $subclass_check);
  }

  /**
   * Checks if the provided format is supported by this normalizer.
   *
   * @param string $format
   *   The format to check.
   *
   * @return bool
   *   TRUE if the format is supported, FALSE otherwise. If no format is
   *   specified this will return TRUE.
   */
  protected function checkFormat($format = NULL) {
    if (!isset($format) || !isset($this->format)) {
      return TRUE;
    }

    return in_array($format, (array) $this->format);
  }

}
