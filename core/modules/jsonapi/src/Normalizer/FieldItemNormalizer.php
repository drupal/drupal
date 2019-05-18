<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInternalPropertiesHelper;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\serialization\Normalizer\CacheableNormalizerInterface;
use Drupal\serialization\Normalizer\SerializedColumnNormalizerTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Converts the Drupal field item object to a JSON:API array structure.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/jsonapi/issues/3032787
 * @see jsonapi.api.php
 */
class FieldItemNormalizer extends NormalizerBase implements DenormalizerInterface {

  use SerializedColumnNormalizerTrait;

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = FieldItemInterface::class;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * FieldItemNormalizer constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   *
   * This normalizer leaves JSON:API normalizer land and enters the land of
   * Drupal core's serialization system. That system was never designed with
   * cacheability in mind, and hence bubbles cacheability out of band. This must
   * catch it, and pass it to the value object that JSON:API uses.
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    /** @var \Drupal\Core\TypedData\TypedDataInterface $property */
    $values = [];
    $context[CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY] = new CacheableMetadata();
    if (!empty($field_item->getProperties(TRUE))) {
      // We normalize each individual value, so each can do their own casting,
      // if needed.
      $field_properties = TypedDataInternalPropertiesHelper::getNonInternalProperties($field_item);
      foreach ($field_properties as $property_name => $property) {
        $values[$property_name] = $this->serializer->normalize($property, $format, $context);
      }
      // Flatten if there is only a single property to normalize.
      $values = static::rasterizeValueRecursive(count($field_properties) == 1 ? reset($values) : $values);
    }
    else {
      $values = $field_item->getValue();
    }
    $normalization = new CacheableNormalization(
      $context[CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY],
      $values
    );
    unset($context[CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY]);
    return $normalization;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    $item_definition = $context['field_definition']->getItemDefinition();
    assert($item_definition instanceof FieldItemDataDefinitionInterface);

    $field_item = $this->getFieldItemInstance($context['resource_type'], $item_definition);
    $this->checkForSerializedStrings($data, $class, $field_item);

    $property_definitions = $item_definition->getPropertyDefinitions();

    $serialized_property_names = $this->getCustomSerializedPropertyNames($field_item);
    $denormalize_property = function ($property_name, $property_value, $property_value_class, $format, $context) use ($serialized_property_names) {
      if ($this->serializer->supportsDenormalization($property_value, $property_value_class, $format, $context)) {
        return $this->serializer->denormalize($property_value, $property_value_class, $format, $context);
      }
      else {
        if (in_array($property_name, $serialized_property_names, TRUE)) {
          $property_value = serialize($property_value);
        }
        return $property_value;
      }
    };
    // Because e.g. the 'bundle' entity key field requires field values to not
    // be expanded to an array of all properties, we special-case single-value
    // properties.
    if (!is_array($data)) {
      $property_value = $data;
      $property_name = $item_definition->getMainPropertyName();
      $property_value_class = $property_definitions[$property_name]->getClass();
      return $denormalize_property($property_name, $property_value, $property_value_class, $format, $context);
    }

    $data_internal = [];
    if (!empty($property_definitions)) {
      foreach ($data as $property_name => $property_value) {
        $property_value_class = $property_definitions[$property_name]->getClass();
        $data_internal[$property_name] = $denormalize_property($property_name, $property_value, $property_value_class, $format, $context);
      }
    }
    else {
      $data_internal = $data;
    }

    return $data_internal;
  }

  /**
   * Gets a field item instance for use with SerializedColumnNormalizerTrait.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The JSON:API resource type of the entity being denormalized.
   * @param \Drupal\Core\Field\TypedData\FieldItemDataDefinitionInterface $item_definition
   *   The field item definition of the instance to get.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getFieldItemInstance(ResourceType $resource_type, FieldItemDataDefinitionInterface $item_definition) {
    if ($bundle_key = $this->entityTypeManager->getDefinition($resource_type->getEntityTypeId())
      ->getKey('bundle')) {
      $create_values = [$bundle_key => $resource_type->getBundle()];
    }
    else {
      $create_values = [];
    }
    $entity = $this->entityTypeManager->getStorage($resource_type->getEntityTypeId())->create($create_values);
    $field = $entity->get($item_definition->getFieldDefinition()->getName());
    assert($field instanceof FieldItemListInterface);
    $field_item = $field->appendItem();
    assert($field_item instanceof FieldItemInterface);
    return $field_item;
  }

}
