<?php

namespace Drupal\serialization\Normalizer;

/**
 * Converts the Drupal entity object structures to a normalized array.
 *
 * This is the default Normalizer for entities. All formats that have Encoders
 * registered with the Serializer in the DIC will be normalized with this
 * class unless another Normalizer is registered which supersedes it. If a
 * module wants to use format-specific or class-specific normalization, then
 * that module can register a new Normalizer and give it a higher priority than
 * this one.
 */
class ComplexDataNormalizer extends NormalizerBase {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\TypedData\ComplexDataInterface';

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $attributes = [];
    /** @var \Drupal\Core\TypedData\TypedDataInterface $field */
    foreach ($object as $name => $field) {
      $attributes[$name] = $this->serializer->normalize($field, $format, $context);
    }
    return $attributes;
  }

}
