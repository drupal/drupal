<?php

namespace Drupal\hal\Normalizer;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\TypedData\TypedDataInternalPropertiesHelper;
use Drupal\serialization\Normalizer\SerializedColumnNormalizerTrait;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

/**
 * Converts the Drupal field item object structure to HAL array structure.
 */
class FieldItemNormalizer extends NormalizerBase {

  use SerializedColumnNormalizerTrait;

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\Field\FieldItemInterface';

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    // The values are wrapped in an array, and then wrapped in another array
    // keyed by field name so that field items can be merged by the
    // FieldNormalizer. This is necessary for the EntityReferenceItemNormalizer
    // to be able to place values in the '_links' array.
    $field = $field_item->getParent();
    return [
      $field->getName() => [$this->normalizedFieldValues($field_item, $format, $context)],
    ];
  }

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

    $field_item = $context['target_instance'];
    $this->checkForSerializedStrings($data, $class, $field_item);

    // If this field is translatable, we need to create a translated instance.
    if (isset($data['lang'])) {
      $langcode = $data['lang'];
      unset($data['lang']);
      $field_definition = $field_item->getFieldDefinition();
      if ($field_definition->isTranslatable()) {
        $field_item = $this->createTranslatedInstance($field_item, $langcode);
      }
    }

    $field_item->setValue($this->constructValue($data, $context));
    return $field_item;
  }

  /**
   * Build the field item value using the incoming data.
   *
   * @param $data
   *   The incoming data for this field item.
   * @param $context
   *   The context passed into the Normalizer.
   *
   * @return mixed
   *   The value to use in Entity::setValue().
   */
  protected function constructValue($data, $context) {
    /** @var \Drupal\Core\Field\FieldItemInterface $field_item */
    $field_item = $context['target_instance'];
    $serialized_property_names = $this->getCustomSerializedPropertyNames($field_item);

    // Explicitly serialize the input, unlike properties that rely on
    // being automatically serialized, manually managed serialized properties
    // expect to receive serialized input.
    foreach ($serialized_property_names as $serialized_property_name) {
      if (!empty($data[$serialized_property_name])) {
        $data[$serialized_property_name] = serialize($data[$serialized_property_name]);
      }
    }

    return $data;
  }

  /**
   * Normalizes field values for an item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $field_item
   *   The field item instance.
   * @param string|null $format
   *   The normalization format.
   * @param array $context
   *   The context passed into the normalizer.
   *
   * @return array
   *   An array of field item values, keyed by property name.
   */
  protected function normalizedFieldValues(FieldItemInterface $field_item, $format, array $context) {
    $normalized = [];
    // We normalize each individual property, so each can do their own casting,
    // if needed.
    /** @var \Drupal\Core\TypedData\TypedDataInterface $property */
    $field_properties = !empty($field_item->getProperties(TRUE))
      ? TypedDataInternalPropertiesHelper::getNonInternalProperties($field_item)
      : $field_item->getValue();
    foreach ($field_properties as $property_name => $property) {
      $normalized[$property_name] = $this->serializer->normalize($property, $format, $context);
    }

    if (isset($context['langcode'])) {
      $normalized['lang'] = $context['langcode'];
    }

    return $normalized;
  }

  /**
   * Get a translated version of the field item instance.
   *
   * To indicate that a field item applies to one translation of an entity and
   * not another, the property path must originate with a translation of the
   * entity. This is the reason for using target_instances, from which the
   * property path can be traversed up to the root.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The untranslated field item instance.
   * @param $langcode
   *   The langcode.
   *
   * @return \Drupal\Core\Field\FieldItemInterface
   *   The translated field item instance.
   */
  protected function createTranslatedInstance(FieldItemInterface $item, $langcode) {
    // Remove the untranslated item that was created for the default language
    // by FieldNormalizer::denormalize().
    $items = $item->getParent();
    $delta = $item->getName();
    unset($items[$delta]);

    // Instead, create a new item for the entity in the requested language.
    $entity = $item->getEntity();
    $entity_translation = $entity->hasTranslation($langcode) ? $entity->getTranslation($langcode) : $entity->addTranslation($langcode);
    $field_name = $item->getFieldDefinition()->getName();
    return $entity_translation->get($field_name)->appendItem();
  }

}
