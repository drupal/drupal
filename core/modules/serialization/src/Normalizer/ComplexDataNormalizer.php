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
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    $attributes = [];
    // $object will not always match $supportedInterfaceOrClass.
    // @see \Drupal\serialization\Normalizer\EntityNormalizer
    // Other normalizers that extend this class may only provide $object that
    // implements \Traversable.
    if ($object instanceof ComplexDataInterface) {
      // If there are no properties to normalize, just normalize the value.
      $object = !empty($object->getProperties(TRUE))
        ? TypedDataInternalPropertiesHelper::getNonInternalProperties($object)
        : $object->getValue();
    }
    /** @var \Drupal\Core\TypedData\TypedDataInterface $property */
    foreach ($object as $name => $property) {
      $attributes[$name] = $this->serializer->normalize($property, $format, $context);
    }
    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function hasCacheableSupportsMethod(): bool {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use getSupportedTypes() instead. See https://www.drupal.org/node/3359695', E_USER_DEPRECATED);

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      ComplexDataInterface::class => TRUE,
    ];
  }

}
