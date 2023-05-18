<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;
use Drupal\jsonapi\ResourceType\ResourceType;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Converts the Drupal field structure to a JSON:API array structure.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class FieldNormalizer extends NormalizerBase implements DenormalizerInterface {

  /**
   * {@inheritdoc}
   */
  public function normalize($field, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    /** @var \Drupal\Core\Field\FieldItemListInterface $field */
    $normalized_items = $this->normalizeFieldItems($field, $format, $context);
    assert($context['resource_object'] instanceof ResourceObject);
    return $context['resource_object']->getResourceType()->getFieldByInternalName($field->getName())->hasOne()
      ? array_shift($normalized_items) ?: CacheableNormalization::permanent(NULL)
      : CacheableNormalization::aggregate($normalized_items);
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []): mixed {
    $field_definition = $context['field_definition'];
    assert($field_definition instanceof FieldDefinitionInterface);
    $resource_type = $context['resource_type'];
    assert($resource_type instanceof ResourceType);

    // If $data contains items (recognizable by numerical array keys, which
    // Drupal's Field API calls "deltas"), then it already is itemized; it's not
    // using the simplified JSON structure that JSON:API generates.
    $is_already_itemized = is_array($data) && array_reduce(array_keys($data), function ($carry, $index) {
      return $carry && is_numeric($index);
    }, TRUE);

    $itemized_data = $is_already_itemized
      ? $data
      : [0 => $data];

    // Single-cardinality fields don't need itemization.
    $field_item_class = $field_definition->getItemDefinition()->getClass();
    if (count($itemized_data) === 1 && $resource_type->getFieldByInternalName($field_definition->getName())->hasOne()) {
      return $this->serializer->denormalize($itemized_data[0], $field_item_class, $format, $context);
    }

    $data_internal = [];
    foreach ($itemized_data as $delta => $field_item_value) {
      $data_internal[$delta] = $this->serializer->denormalize($field_item_value, $field_item_class, $format, $context);
    }

    return $data_internal;
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
   * @return \Drupal\jsonapi\Normalizer\FieldItemNormalizer[]
   *   The array of normalized field items.
   */
  protected function normalizeFieldItems(FieldItemListInterface $field, $format, array $context) {
    $normalizer_items = [];
    if (!$field->isEmpty()) {
      foreach ($field as $field_item) {
        $normalizer_items[] = $this->serializer->normalize($field_item, $format, $context);
      }
    }
    return $normalizer_items;
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
      FieldItemListInterface::class => TRUE,
    ];
  }

}
