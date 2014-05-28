<?php

/**
 * @file
 * Contains \Drupal\hal\Normalizer\NormalizerBase.
 */

namespace Drupal\hal\Normalizer;

use Drupal\serialization\EntityResolver\EntityResolverInterface;
use Drupal\serialization\Normalizer\NormalizerBase as SerializationNormalizerBase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Base class for Normalizers.
 */
abstract class NormalizerBase extends SerializationNormalizerBase implements DenormalizerInterface {

  /**
   * The formats that the Normalizer can handle.
   *
   * @var array
   */
  protected $formats = array('hal_json');

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface::supportsNormalization().
   */
  public function supportsNormalization($data, $format = NULL) {
    return in_array($format, $this->formats) && parent::supportsNormalization($data, $format);
  }

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\DenormalizerInterface::supportsDenormalization()
   */
  public function supportsDenormalization($data, $type, $format = NULL) {
    if (in_array($format, $this->formats) && (class_exists($this->supportedInterfaceOrClass) || interface_exists($this->supportedInterfaceOrClass))) {
      $target = new \ReflectionClass($type);
      $supported = new \ReflectionClass($this->supportedInterfaceOrClass);
      if ($supported->isInterface()) {
        return $target->implementsInterface($this->supportedInterfaceOrClass);
      }
      else {
        return ($target->getName() == $this->supportedInterfaceOrClass || $target->isSubclassOf($this->supportedInterfaceOrClass));
      }
    }

    return FALSE;
  }

}
