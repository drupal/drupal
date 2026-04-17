<?php

declare(strict_types=1);

namespace Drupal\test_datatype_boolean_emoji_normalizer\Normalizer;

use Drupal\Core\TypedData\Plugin\DataType\BooleanData;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Normalizes boolean data weirdly: renders them as 👍 (TRUE) or 👎 (FALSE).
 */
class BooleanNormalizer extends NormalizerBase implements DenormalizerInterface {

  /**
   * Normalizes data into a set of arrays/scalars.
   *
   * @param object $object
   *   Data to normalize.
   * @param string|null $format
   *   Format the normalization result will be encoded as.
   * @param array<string, mixed> $context
   *   Context options for the normalizer.
   *
   * @return string
   *   The normalized data.
   */
  public function normalize($object, $format = NULL, array $context = []): string {
    return $object->getValue() ? '👍' : '👎';
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []): mixed {
    if (!in_array($data, ['👍', '👎'], TRUE)) {
      throw new \UnexpectedValueException('Only 👍 and 👎 are acceptable values.');
    }
    return $data === '👍';
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [BooleanData::class => TRUE];
  }

}
