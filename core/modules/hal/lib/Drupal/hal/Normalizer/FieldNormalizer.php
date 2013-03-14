<?php

/**
 * @file
 * Contains \Drupal\hal\Normalizer\FieldNormalizer.
 */

namespace Drupal\hal\Normalizer;

use Drupal\Component\Utility\NestedArray;

/**
 * Converts the Drupal field structure to HAL array structure.
 */
class FieldNormalizer extends NormalizerBase {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\Entity\Field\FieldInterface';

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize()
   */
  public function normalize($field, $format = NULL, array $context = array()) {
    $normalized_field_items = array();
    $entity = $field->getParent();
    $field_name = $field->getName();
    $field_definition = $entity->getPropertyDefinition($field_name);

    // If this field is not translatable, it can simply be normalized without
    // separating it into different translations.
    if (empty($field_definition['translatable'])) {
      $normalized_field_items = $this->normalizeFieldItems($field, $format, $context);
    }
    // Otherwise, the languages have to be extracted from the entity and passed
    // in to the field item normalizer in the context. The langcode is appended
    // to the field item values.
    else {
      foreach ($entity->getTranslationLanguages() as $lang) {
        $context['langcode'] = $lang->langcode == 'und' ? LANGUAGE_DEFAULT : $lang->langcode;
        $translation = $entity->getTranslation($lang->langcode);
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
   * @param \Drupal\Core\Entity\Field\FieldInterface $field
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
    $normalized_field_items = array();
    if (!$field->isEmpty()) {
      foreach ($field as $field_item) {
        $normalized_field_items[] = $this->serializer->normalize($field_item, $format, $context);
      }
    }
    return $normalized_field_items;
  }

}
