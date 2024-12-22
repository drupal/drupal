<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\serialization\Normalizer\SchematicNormalizerTrait;
use Drupal\serialization\Serializer\JsonSchemaProviderSerializerInterface;
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

  use SchematicNormalizerTrait;

  /**
   * {@inheritdoc}
   */
  public function doNormalize($field, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
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
   * @return \Drupal\jsonapi\Normalizer\Value\CacheableNormalization[]
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
  protected function getNormalizationSchema(mixed $object, array $context = []): array {
    assert($object instanceof FieldItemListInterface);
    // Some aspects of the schema are determined by the field config.
    $cardinality = $object->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();
    $schema = [];
    // Normalizers are resolved by the class/object being normalized. Even
    // without data, we must retrieve a representative field item.
    $field_item = $object->appendItem($object->generateSampleItems());
    assert($this->serializer instanceof JsonSchemaProviderSerializerInterface);
    $item_schema = $this->serializer->getJsonSchema($field_item, $context);
    $object->removeItem(count($object) - 1);
    unset($field_item);

    $schema = $item_schema;
    if ($cardinality !== 1) {
      $schema['type'] = 'array';
      if ($object->getFieldDefinition()->isRequired()) {
        $schema['minItems'] = 1;
      }
      if ($cardinality !== FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
        $schema['maxItems'] = $cardinality;
      }
      if (!empty($item_schema)) {
        $schema['items'] = $item_schema;
      }
    }
    return !$object->getFieldDefinition()->isRequired()
      ? ['oneOf' => [$schema, ['type' => 'null']]]
      : $schema;
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
