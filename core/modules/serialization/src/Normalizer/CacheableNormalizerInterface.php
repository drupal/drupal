<?php

namespace Drupal\serialization\Normalizer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Defines the interface for normalizers producing cacheable normalizations.
 *
 * @see cache
 */
interface CacheableNormalizerInterface extends NormalizerInterface {

  /**
   * Name of key for bubbling cacheability metadata via serialization context.
   *
   * @see \Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize()
   * @see \Symfony\Component\Serializer\SerializerInterface::serialize()
   * @see \Drupal\rest\EventSubscriber\ResourceResponseSubscriber::renderResponseBody()
   */
  const SERIALIZATION_CONTEXT_CACHEABILITY = 'cacheability';

}
