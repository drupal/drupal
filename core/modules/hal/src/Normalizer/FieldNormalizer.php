<?php

namespace Drupal\hal\Normalizer;

use Drupal\Component\Utility\NestedArray;
use Drupal\serialization\Normalizer\FieldNormalizer as SerializationFieldNormalizer;

/**
 * Converts the Drupal field structure to HAL array structure.
 */
class FieldNormalizer extends SerializationFieldNormalizer {

  /**
   * The formats that the Normalizer can handle.
   *
   * @var array
   */
  protected $format = ['hal_json'];

  /**
   * {@inheritdoc}
   */
  public function normalize($field, $format = NULL, array $context = []) {
    $normalized_field_items = [];

    // Get the field definition.
    $entity = $field->getEntity();
    $field_name = $field->getName();
    $field_definition = $field->getFieldDefinition();

    // If this field is not translatable, it can simply be normalized without
    // separating it into different translations.
    if (!$field_definition->isTranslatable()) {
      $normalized_field_items = $this->normalizeFieldItems($field, $format, $context);
    }
    // Otherwise, the languages have to be extracted from the entity and passed
    // in to the field item normalizer in the context. The langcode is appended
    // to the field item values.
    else {
      foreach ($entity->getTranslationLanguages() as $language) {
        $context['langcode'] = $language->getId();
        $translation = $entity->getTranslation($language->getId());
        $translated_field = $translation->get($field_name);
        $normalized_field_items = array_merge($normalized_field_items, $this->normalizeFieldItems($translated_field, $format, $context));
      }
    }

    // Merge deep so that links set in entity reference normalizers are merged
    // into the links property.
    $normalized = NestedArray::mergeDeepArray($normalized_field_items);
    return $normalized;
  }

  /**
   * Helper function to normalize field items.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field object.
   * @param string $format
   *   The format.
   * @param array $context
   *   The context array.
   *
   * @return array
   *   The array of normalized field items.
   */
  protected function normalizeFieldItems($field, $format, $context) {
    $normalized_field_items = [];
    if (!$field->isEmpty()) {
      foreach ($field as $field_item) {
        $normalized_field_items[] = $this->serializer->normalize($field_item, $format, $context);
      }
    }
    return $normalized_field_items;
  }

}
