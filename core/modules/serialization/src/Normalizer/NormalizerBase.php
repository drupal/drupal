<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

/**
 * Base class for Normalizers.
 */
abstract class NormalizerBase implements SerializerAwareInterface, CacheableNormalizerInterface {

  use SerializerAwareTrait;

  /**
   * List of formats which supports (de-)normalization.
   *
   * @var string|string[]
   */
  protected $format;

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, ?string $format = NULL, array $context = []): bool {
    // If we aren't dealing with an object or the format is not supported return
    // now.
    if (!is_object($data) || !$this->checkFormat($format)) {
      return FALSE;
    }
    $supported = array_keys($this->getSupportedTypes($format));

    return (bool) array_filter($supported, function ($name) use ($data) {
      return $data instanceof $name;
    });
  }

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\DenormalizerInterface::supportsDenormalization()
   *
   * This class doesn't implement DenormalizerInterface, but most of its child
   * classes do. Therefore, this method is implemented at this level to reduce
   * code duplication.
   */
  public function supportsDenormalization($data, string $type, ?string $format = NULL, array $context = []): bool {
    // If the format is not supported return now.
    if (!$this->checkFormat($format)) {
      return FALSE;
    }

    $supported = array_keys($this->getSupportedTypes($format));

    $subclass_check = function ($name) use ($type) {
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

    return in_array($format, (array) $this->format, TRUE);
  }

  /**
   * Adds cacheability if applicable.
   *
   * @param array $context
   *   Context options for the normalizer.
   * @param $data
   *   The data that might have cacheability information.
   */
  protected function addCacheableDependency(array $context, $data) {
    if ($data instanceof CacheableDependencyInterface && isset($context[static::SERIALIZATION_CONTEXT_CACHEABILITY])) {
      $context[static::SERIALIZATION_CONTEXT_CACHEABILITY]->addCacheableDependency($data);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      '*' => FALSE,
    ];
  }

}
