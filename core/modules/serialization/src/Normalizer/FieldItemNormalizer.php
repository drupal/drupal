<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\Field\FieldItemInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Denormalizes field item object structure by updating the entity field values.
 */
class FieldItemNormalizer extends ComplexDataNormalizer implements DenormalizerInterface {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = FieldItemInterface::class;

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    if (!isset($context['target_instance'])) {
      throw new InvalidArgumentException('$context[\'target_instance\'] must be set to denormalize with the FieldItemNormalizer');
    }

    if ($context['target_instance']->getParent() == NULL) {
      throw new InvalidArgumentException('The field item passed in via $context[\'target_instance\'] must have a parent set.');
    }

    /** @var \Drupal\Core\Field\FieldItemInterface $field_item */
    $field_item = $context['target_instance'];

    $field_item->setValue($this->constructValue($data, $context));
    return $field_item;
  }

  /**
   * Build the field item value using the incoming data.
   *
   * Most normalizers that extend this class can simply use this method to
   * construct the denormalized value without having to override denormalize()
   * and reimplementing its validation logic or its call to set the field value.
   *
   * @param mixed $data
   *   The incoming data for this field item.
   * @param array $context
   *   The context passed into the Normalizer.
   *
   * @return mixed
   *   The value to use in Entity::setValue().
   */
  protected function constructValue($data, $context) {
    return $data;
  }

}
