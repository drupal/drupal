<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\Field\FieldItemInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Denormalizes field item object structure by updating the entity field values.
 */
class FieldItemNormalizer extends ComplexDataNormalizer implements DenormalizerInterface {

  use FieldableEntityNormalizerTrait;
  use SerializedColumnNormalizerTrait;

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = FieldItemInterface::class;

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []): mixed {
    if (!isset($context['target_instance'])) {
      throw new InvalidArgumentException('$context[\'target_instance\'] must be set to denormalize with the FieldItemNormalizer');
    }

    if ($context['target_instance']->getParent() == NULL) {
      throw new InvalidArgumentException('The field item passed in via $context[\'target_instance\'] must have a parent set.');
    }

    /** @var \Drupal\Core\Field\FieldItemInterface $field_item */
    $field_item = $context['target_instance'];
    $this->checkForSerializedStrings($data, $class, $field_item);

    $field_item->setValue($this->constructValue($data, $context));
    return $field_item;
  }

}
