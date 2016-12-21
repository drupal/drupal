<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\Field\FieldItemListInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Denormalizes data to Drupal field values.
 *
 * This class simply calls denormalize() on the individual FieldItems. The
 * FieldItem normalizers are responsible for setting the field values for each
 * item.
 *
 * @see \Drupal\serialization\Normalizer\FieldItemNormalizer.
 */
class FieldNormalizer extends ListNormalizer implements DenormalizerInterface {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = FieldItemListInterface::class;

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    if (!isset($context['target_instance'])) {
      throw new InvalidArgumentException('$context[\'target_instance\'] must be set to denormalize with the FieldNormalizer');
    }

    /** @var FieldItemListInterface $items */
    $items = $context['target_instance'];
    $item_class = $items->getItemDefinition()->getClass();
    foreach ($data as $item_data) {
      // Create a new item and pass it as the target for the unserialization of
      // $item_data. All items in field should have removed before this method
      // was called.
      // @see \Drupal\serialization\Normalizer\ContentEntityNormalizer::denormalize().
      $context['target_instance'] = $items->appendItem();
      $this->serializer->denormalize($item_data, $item_class, $format, $context);
    }
    return $items;
  }

}
