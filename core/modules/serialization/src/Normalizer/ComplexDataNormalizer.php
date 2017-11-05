<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\TypedDataInternalPropertiesHelper;

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
    // $object will not always match $supportedInterfaceOrClass.
    // @see \Drupal\serialization\Normalizer\EntityNormalizer
    // Other normalizers that extend this class may only provide $object that
    // implements \Traversable.
    if ($object instanceof ComplexDataInterface) {
      $object = TypedDataInternalPropertiesHelper::getNonInternalProperties($object);
    }
    /** @var \Drupal\Core\TypedData\TypedDataInterface $property */
    foreach ($object as $name => $property) {
      $attributes[$name] = $this->serializer->normalize($property, $format, $context);
    }
    return $attributes;
  }

}
