<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinitionInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInternalPropertiesHelper;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\serialization\Normalizer\CacheableNormalizerInterface;
use Drupal\serialization\Normalizer\JsonSchemaReflectionTrait;
use Drupal\serialization\Normalizer\SchematicNormalizerTrait;
use Drupal\serialization\Normalizer\SerializedColumnNormalizerTrait;
use Drupal\serialization\Serializer\JsonSchemaProviderSerializerInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Converts the Drupal field item object to a JSON:API array structure.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class FieldItemNormalizer extends NormalizerBase implements DenormalizerInterface {

  use SerializedColumnNormalizerTrait;
  use SchematicNormalizerTrait;
  use JsonSchemaReflectionTrait;

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
  public function doNormalize($object, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    assert($object instanceof FieldItemInterface);
    /** @var \Drupal\Core\TypedData\TypedDataInterface $property */
    $context[CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY] = new CacheableMetadata();
    // Default: The field has only internal (or no) properties but has a public
    // value.
    $values = $object->getValue();
    // There are non-internal properties. Normalize those.
    if ($field_properties = TypedDataInternalPropertiesHelper::getNonInternalProperties($object)) {
      // We normalize each individual value, so each can do their own casting,
      // if needed.
      $values = array_map(function ($property) use ($format, $context) {
        return $this->serializer->normalize($property, $format, $context);
      }, $field_properties);
      // Flatten if there is only a single property to normalize.
      $flatten = count($field_properties) === 1 && $object::mainPropertyName() !== NULL;
      $values = static::rasterizeValueRecursive($flatten ? reset($values) : $values);
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
  public function denormalize($data, $class, $format = NULL, array $context = []): mixed {
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
      // The NULL normalization means there is no value, hence we can return
      // early. Note that this is not just an optimization but a necessity for
      // field types without main properties (such as the "map" field type).
      if ($data === NULL) {
        return $data;
      }
      $property_value = $data;
      $property_name = $item_definition->getMainPropertyName();
      $property_value_class = $property_definitions[$property_name]->getClass();
      return $denormalize_property($property_name, $property_value, $property_value_class, $format, $context);
    }

    $data_internal = [];
    if (!empty($property_definitions)) {
      $writable_properties = array_keys(array_filter($property_definitions, function (DataDefinitionInterface $data_definition) : bool {
        return !$data_definition->isReadOnly();
      }));
      $invalid_property_names = [];
      foreach ($data as $property_name => $property_value) {
        if (!isset($property_definitions[$property_name])) {
          $alt = static::getAlternatives($property_name, $writable_properties);
          $invalid_property_names[$property_name] = reset($alt);
        }
      }
      if (!empty($invalid_property_names)) {
        $suggestions = array_values(array_filter($invalid_property_names));
        // Only use the "Did you mean"-style error message if there is a
        // suggestion for every invalid property name.
        if (count($suggestions) === count($invalid_property_names)) {
          $format = count($invalid_property_names) === 1
            ? "The property '%s' does not exist on the '%s' field of type '%s'. Did you mean '%s'?"
            : "The properties '%s' do not exist on the '%s' field of type '%s'. Did you mean '%s'?";
          throw new UnexpectedValueException(sprintf(
            $format,
            implode("', '", array_keys($invalid_property_names)),
            $item_definition->getFieldDefinition()->getName(),
            $item_definition->getFieldDefinition()->getType(),
            implode("', '", $suggestions)
          ));
        }
        else {
          $format = count($invalid_property_names) === 1
            ? "The property '%s' does not exist on the '%s' field of type '%s'. Writable properties are: '%s'."
            : "The properties '%s' do not exist on the '%s' field of type '%s'. Writable properties are: '%s'.";
          throw new UnexpectedValueException(sprintf(
            $format,
            implode("', '", array_keys($invalid_property_names)),
            $item_definition->getFieldDefinition()->getName(),
            $item_definition->getFieldDefinition()->getType(),
            implode("', '", $writable_properties)
          ));
        }
      }

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
   * Provides alternatives for a given array and key.
   *
   * @param string $search_key
   *   The search key to get alternatives for.
   * @param array $keys
   *   The search space to search for alternatives in.
   *
   * @return string[]
   *   An array of strings with suitable alternatives.
   *
   * @see \Drupal\Component\DependencyInjection\Container::getAlternatives()
   */
  private static function getAlternatives(string $search_key, array $keys) : array {
    // $search_key is user input and could be longer than the 255 string length
    // limit of levenshtein().
    if (strlen($search_key) > 255) {
      return [];
    }

    $alternatives = [];
    foreach ($keys as $key) {
      $lev = levenshtein($search_key, $key);
      if ($lev <= strlen($search_key) / 3 || str_contains($key, $search_key)) {
        $alternatives[] = $key;
      }
    }

    return $alternatives;
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

  /**
   * {@inheritdoc}
   */
  protected function getNormalizationSchema(mixed $object, array $context = []): array {
    $schema = ['type' => 'object'];
    if (is_string($object)) {
      return ['$comment' => 'No detailed schema available.'] + $schema;
    }
    assert($object instanceof FieldItemInterface);
    $field_properties = TypedDataInternalPropertiesHelper::getNonInternalProperties($object);
    if (count($field_properties) === 0) {
      // The field item has only internal (or no) properties. In this case, the
      // value is normalized from ::getValue(). Use a schema from the method or
      // interface, if available.
      return $this->getJsonSchemaForMethod(
        $object,
        'getValue',
        ['$comment' => sprintf('Cannot determine schema for %s::getValue().', $object::class)]
      );
    }
    // If we did not early return, iterate over the non-internal properties.
    foreach ($field_properties as $property_name => $property) {
      $property_schema = [
        'title' => (string) $property->getDataDefinition()->getLabel(),
      ];
      assert($this->serializer instanceof JsonSchemaProviderSerializerInterface);
      $property_schema = array_merge(
        $this->serializer->getJsonSchema($property, $context),
        $property_schema,
      );
      $schema['properties'][$property_name] = $property_schema;
    }
    // Flatten if there is only a single property to normalize.
    if (count($field_properties) === 1 && $object::mainPropertyName() !== NULL) {
      $schema = $schema['properties'][$object::mainPropertyName()] ?? [];
    }
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      FieldItemInterface::class => TRUE,
    ];
  }

}
