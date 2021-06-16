<?php

namespace Drupal\test_datatype_boolean_emoji_normalizer\Normalizer;

use Drupal\Core\TypedData\Plugin\DataType\BooleanData;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Normalizes boolean data weirdly: renders them as 👍 (TRUE) or 👎 (FALSE).
 */
class BooleanNormalizer extends NormalizerBase implements DenormalizerInterface {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = BooleanData::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    return $object->getValue() ? '👍' : '👎';
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    if (!in_array($data, ['👍', '👎'], TRUE)) {
      throw new \UnexpectedValueException('Only 👍 and 👎 are acceptable values.');
    }
    return $data === '👍';
  }

}
