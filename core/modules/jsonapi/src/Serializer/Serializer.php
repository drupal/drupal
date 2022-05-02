<?php

namespace Drupal\jsonapi\Serializer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer as SymfonySerializer;

/**
 * Overrides the Symfony serializer to cordon off our incompatible normalizers.
 *
 * This service is for *internal* use only. It is not suitable for *any* reuse.
 * Backwards compatibility is in no way guaranteed and will almost certainly be
 * broken in the future.
 *
 * @link https://www.drupal.org/project/drupal/issues/2923779#comment-12407443
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
final class Serializer extends SymfonySerializer {

  /**
   * A normalizer to fall back on when JSON:API cannot normalize an object.
   *
   * @var \Symfony\Component\Serializer\Normalizer\NormalizerInterface|\Symfony\Component\Serializer\Normalizer\DenormalizerInterface
   */
  protected $fallbackNormalizer;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $normalizers = [], array $encoders = []) {
    foreach ($normalizers as $normalizer) {
      if (strpos(get_class($normalizer), 'Drupal\jsonapi\Normalizer') !== 0) {
        throw new \LogicException('JSON:API does not allow adding more normalizers!');
      }
    }
    parent::__construct($normalizers, $encoders);
  }

  /**
   * Adds a secondary normalizer.
   *
   * This normalizer will be attempted when JSON:API has no applicable
   * normalizer.
   *
   * @param \Symfony\Component\Serializer\Normalizer\NormalizerInterface $normalizer
   *   The secondary normalizer.
   */
  public function setFallbackNormalizer(NormalizerInterface $normalizer) {
    $this->fallbackNormalizer = $normalizer;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($data, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    if ($this->selfSupportsNormalization($data, $format, $context)) {
      return parent::normalize($data, $format, $context);
    }
    if ($this->fallbackNormalizer->supportsNormalization($data, $format, $context)) {
      return $this->fallbackNormalizer->normalize($data, $format, $context);
    }
    return parent::normalize($data, $format, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $type, $format = NULL, array $context = []): mixed {
    if ($this->selfSupportsDenormalization($data, $type, $format, $context)) {
      return parent::denormalize($data, $type, $format, $context);
    }
    return $this->fallbackNormalizer->denormalize($data, $type, $format, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, string $format = NULL, array $context = []): bool {
    return $this->selfSupportsNormalization($data, $format, $context) || $this->fallbackNormalizer->supportsNormalization($data, $format, $context);
  }

  /**
   * Checks whether this class alone supports normalization.
   *
   * @param mixed $data
   *   Data to normalize.
   * @param string $format
   *   The format being (de-)serialized from or into.
   * @param array $context
   *   (optional) Options available to the normalizer.
   *
   * @return bool
   *   Whether this class supports normalization for the given data.
   */
  private function selfSupportsNormalization($data, $format = NULL, array $context = []) {
    return parent::supportsNormalization($data, $format, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDenormalization($data, string $type, string $format = NULL, array $context = []): bool {
    return $this->selfSupportsDenormalization($data, $type, $format, $context) || $this->fallbackNormalizer->supportsDenormalization($data, $type, $format, $context);
  }

  /**
   * Checks whether this class alone supports denormalization.
   *
   * @param mixed $data
   *   Data to denormalize from.
   * @param string $type
   *   The class to which the data should be denormalized.
   * @param string $format
   *   The format being deserialized from.
   * @param array $context
   *   (optional) Options available to the denormalizer.
   *
   * @return bool
   *   Whether this class supports normalization for the given data and type.
   */
  private function selfSupportsDenormalization($data, $type, $format = NULL, array $context = []) {
    return parent::supportsDenormalization($data, $type, $format, $context);
  }

}
