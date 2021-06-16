<?php

namespace Drupal\test_fieldtype_boolean_emoji_normalizer\Normalizer;

use Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem;
use Drupal\serialization\Normalizer\FieldItemNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Normalizes boolean fields weirdly: renders them as 👍 (TRUE) or 👎 (FALSE).
 */
class BooleanItemNormalizer extends FieldItemNormalizer implements DenormalizerInterface {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = BooleanItem::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $data = parent::normalize($object, $format, $context);
    $data['value'] = $data['value'] ? '👍' : '👎';
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function constructValue($data, $context) {
    // Just like \Drupal\serialization\Normalizer\FieldItemNormalizer's logic
    // for denormalization, which uses TypedDataInterface::setValue(), allow the
    // keying by main property name ("value") to be implied.
    if (!is_array($data)) {
      $data = ['value' => $data];
    }

    if (!in_array($data['value'], ['👍', '👎'], TRUE)) {
      throw new \UnexpectedValueException('Only 👍 and 👎 are acceptable values.');
    }
    $data['value'] = ($data['value'] === '👍');
    return $data;
  }

}
