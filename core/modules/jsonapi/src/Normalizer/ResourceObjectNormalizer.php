<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jsonapi\Events\CollectRelationshipMetaEvent;
use Drupal\jsonapi\Events\CollectResourceObjectMetaEvent;
use Drupal\jsonapi\EventSubscriber\ResourceObjectNormalizationCacher;
use Drupal\jsonapi\JsonApiResource\Relationship;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiSpec;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;
use Drupal\jsonapi\Normalizer\Value\CacheableOmission;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeField;
use Drupal\jsonapi\Serializer\Serializer;
use Drupal\serialization\Normalizer\SchematicNormalizerTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Converts the JSON:API module ResourceObject into a JSON:API array structure.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class ResourceObjectNormalizer extends NormalizerBase {

  use SchematicNormalizerTrait;

  /**
   * The entity normalization cacher.
   *
   * @var \Drupal\jsonapi\EventSubscriber\ResourceObjectNormalizationCacher
   */
  protected $cacher;

  /**
   * @var mixed|\Symfony\Component\EventDispatcher\EventDispatcherInterface|null
   */
  private EventDispatcherInterface $eventDispatcher;

  /**
   * @var mixed|\Drupal\Core\Entity\EntityFieldManagerInterface|null
   */
  private EntityFieldManagerInterface $entityFieldManager;

  /**
   * @var mixed|\Drupal\Core\Entity\EntityTypeManagerInterface|null
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a ResourceObjectNormalizer object.
   *
   * @param \Drupal\jsonapi\EventSubscriber\ResourceObjectNormalizationCacher $cacher
   *   The entity normalization cacher.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ResourceObjectNormalizationCacher $cacher, ?EventDispatcherInterface $event_dispatcher = NULL, ?EntityFieldManagerInterface $entity_field_manager = NULL, ?EntityTypeManagerInterface $entity_type_manager = NULL) {
    $this->cacher = $cacher;

    if ($event_dispatcher === NULL) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $event_dispatcher argument is deprecated in drupal:11.2.0 and will be required in drupal:12.0.0. See https://www.drupal.org/node/3280569', E_USER_DEPRECATED);
      $event_dispatcher = \Drupal::service('event_dispatcher');
    }
    $this->eventDispatcher = $event_dispatcher;

    if ($entity_field_manager === NULL) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $entity_field_manager argument is deprecated in drupal:11.2.0 and will be required in drupal:12.0.0. See https://www.drupal.org/node/3031367', E_USER_DEPRECATED);
      $entity_field_manager = \Drupal::service('entity_field_manager');
    }
    $this->entityFieldManager = $entity_field_manager;

    if ($entity_type_manager === NULL) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $entity_type_manager argument is deprecated in drupal:11.2.0 and will be required in drupal:12.0.0. See https://www.drupal.org/node/3031367', E_USER_DEPRECATED);
      $entity_type_manager = \Drupal::service('entity_type_manager');
    }
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDenormalization($data, string $type, ?string $format = NULL, array $context = []): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function doNormalize($object, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    assert($object instanceof ResourceObject);
    // If the fields to use were specified, only output those field values.
    $context['resource_object'] = $object;
    $resource_type = $object->getResourceType();
    $resource_type_name = $resource_type->getTypeName();
    $fields = $object->getFields();
    // Get the bundle ID of the requested resource. This is used to determine if
    // this is a bundle level resource or an entity level resource.
    if (!empty($context['sparse_fieldset'][$resource_type_name])) {
      $field_names = $context['sparse_fieldset'][$resource_type_name];
    }
    else {
      $field_names = array_keys($fields);
    }

    $normalization_parts = $this->getNormalization($field_names, $object, $format, $context);

    // Keep only the requested fields (the cached normalization gradually grows
    // to the complete set of fields).
    $fields = $normalization_parts[ResourceObjectNormalizationCacher::RESOURCE_CACHE_SUBSET_FIELDS];
    $field_normalizations = array_intersect_key($fields, array_flip($field_names));

    $relationship_field_names = array_keys($resource_type->getRelatableResourceTypes());
    $attributes = array_diff_key($field_normalizations, array_flip($relationship_field_names));
    $relationships = array_intersect_key($field_normalizations, array_flip($relationship_field_names));

    $event = new CollectResourceObjectMetaEvent($object, $context);
    $this->eventDispatcher->dispatch($event);

    $entity_normalization = array_filter(
      $normalization_parts[ResourceObjectNormalizationCacher::RESOURCE_CACHE_SUBSET_BASE] + [
        'attributes' => CacheableNormalization::aggregate($attributes)->omitIfEmpty(),
        'relationships' => CacheableNormalization::aggregate($relationships)->omitIfEmpty(),
        'meta' => (count($event->getMeta()) > 0) ? new CacheableNormalization($event, $event->getMeta()) : '',
      ]
    );
    return CacheableNormalization::aggregate($entity_normalization)->withCacheableDependency($object);
  }

  /**
   * Normalizes an entity using the given fieldset.
   *
   * @param string[] $field_names
   *   The field names to normalize (the sparse fieldset, if any).
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObject $object
   *   The resource object to partially normalize.
   * @param string $format
   *   The format in which the normalization will be encoded.
   * @param array $context
   *   Context options for the normalizer.
   *
   * @return array
   *   An array with two key-value pairs:
   *   - 'base': array, the base normalization of the entity, that does not
   *             depend on which sparse fieldset was requested.
   *   - 'fields': CacheableNormalization for all requested fields.
   *
   * @see ::normalize()
   */
  protected function getNormalization(array $field_names, ResourceObject $object, $format = NULL, array $context = []) {
    $cached_normalization_parts = $this->cacher->get($object);
    $normalizer_values = $cached_normalization_parts !== FALSE
      ? $cached_normalization_parts
      : static::buildEmptyNormalization($object);
    $fields = &$normalizer_values[ResourceObjectNormalizationCacher::RESOURCE_CACHE_SUBSET_FIELDS];
    $non_cached_fields = array_diff_key($object->getFields(), $fields);
    $non_cached_requested_fields = array_intersect_key($non_cached_fields, array_flip($field_names));
    foreach ($non_cached_requested_fields as $field_name => $field) {
      $fields[$field_name] = $this->serializeField($field, $context, $format);
    }
    // Add links if missing.
    $base = &$normalizer_values[ResourceObjectNormalizationCacher::RESOURCE_CACHE_SUBSET_BASE];
    $base['links'] = $base['links'] ?? $this->serializer->normalize($object->getLinks(), $format, $context)->omitIfEmpty();

    if (!empty($non_cached_requested_fields)) {
      $this->cacher->saveOnTerminate($object, $normalizer_values);
    }

    return $normalizer_values;
  }

  /**
   * Builds the empty normalization structure for cache misses.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObject $object
   *   The resource object being normalized.
   *
   * @return array
   *   The normalization structure as defined in ::getNormalization().
   *
   * @see ::getNormalization()
   */
  protected static function buildEmptyNormalization(ResourceObject $object) {
    return [
      ResourceObjectNormalizationCacher::RESOURCE_CACHE_SUBSET_BASE => [
        'type' => CacheableNormalization::permanent($object->getResourceType()->getTypeName()),
        'id' => CacheableNormalization::permanent($object->getId()),
      ],
      ResourceObjectNormalizationCacher::RESOURCE_CACHE_SUBSET_FIELDS => [],
    ];
  }

  /**
   * Serializes a given field.
   *
   * @param mixed $field
   *   The field to serialize.
   * @param array $context
   *   The normalization context.
   * @param string $format
   *   The serialization format.
   *
   * @return \Drupal\jsonapi\Normalizer\Value\CacheableNormalization
   *   The normalized value.
   */
  protected function serializeField($field, array $context, $format) {
    // Only content entities contain FieldItemListInterface fields. Since config
    // entities do not have "real" fields and therefore do not have field access
    // restrictions.
    if ($field instanceof FieldItemListInterface) {
      $field_access_result = $field->access('view', $context['account'] ?? NULL, TRUE);
      if (!$field_access_result->isAllowed()) {
        return new CacheableOmission(CacheableMetadata::createFromObject($field_access_result));
      }
      if ($field instanceof EntityReferenceFieldItemListInterface) {
        // Build the relationship object based on the entity reference and
        // normalize that object instead.
        assert(!empty($context['resource_object']) && $context['resource_object'] instanceof ResourceObject);
        $resource_object = $context['resource_object'];

        $resource_field_name = $resource_object->getResourceType()->getFieldByInternalName($field->getName())->getPublicName();
        $collect_meta_event = new CollectRelationshipMetaEvent($resource_object, $resource_field_name);
        $this->eventDispatcher->dispatch($collect_meta_event);
        $relationship = Relationship::createFromEntityReferenceField(context: $resource_object, field: $field, meta: $collect_meta_event->getMeta());
        $normalized_field = $this->serializer->normalize($relationship, $format, $context);
        $normalized_field = $normalized_field->withCacheableDependency($collect_meta_event);
      }
      else {
        $normalized_field = $this->serializer->normalize($field, $format, $context);
      }
      assert($normalized_field instanceof CacheableNormalization);
      return $normalized_field->withCacheableDependency(CacheableMetadata::createFromObject($field_access_result));
    }
    else {
      // @todo Replace this workaround after https://www.drupal.org/node/3043245
      //   or remove the need for this in https://www.drupal.org/node/2942975.
      //   See \Drupal\layout_builder\Normalizer\LayoutEntityDisplayNormalizer.
      if (is_a($context['resource_object']->getResourceType()->getDeserializationTargetClass(), 'Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay', TRUE) && $context['resource_object']->getField('third_party_settings') === $field) {
        unset($field['layout_builder']['sections']);
      }

      // Config "fields" in this case are arrays or primitives and do not need
      // to be normalized.
      return CacheableNormalization::permanent($field);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getNormalizationSchema(mixed $object, array $context = []): array {
    if (is_string($object)) {
      // Without a true object we can only provide a generic schema.
      return [
        '$ref' => JsonApiSpec::SUPPORTED_SPECIFICATION_JSON_SCHEMA . '#/definitions/resource',
      ];
    }
    // ResourceObject is usually instantiated from a specific entity, however
    // a placeholder can be created for purposes of schema generation which
    // would provide access to the resource type, but not contain any "live"
    // data.
    assert($object instanceof ResourceObject);

    $attributes_schema = [];
    $relationships_schema = [];

    $this->entityTypeManager->getDefinition($object->getResourceType()->getEntityTypeId())->entityClassImplements(FieldableEntityInterface::class)
      ? $this->processContentEntitySchema($object, $context, $attributes_schema, $relationships_schema)
      : $this->processConfigEntitySchema($object->getResourceType(), $context, $attributes_schema);

    return [
      'allOf' => [
        ['$ref' => JsonApiSpec::SUPPORTED_SPECIFICATION_JSON_SCHEMA . '#/definitions/resourceIdentification'],
      ],
      'properties' => [
        'meta' => [
          '$ref' => JsonApiSpec::SUPPORTED_SPECIFICATION_JSON_SCHEMA . '#/definitions/meta',
        ],
        'links' => [
          '$ref' => JsonApiSpec::SUPPORTED_SPECIFICATION_JSON_SCHEMA . '#/definitions/resourceLinks',
        ],
        // If the array is empty we must return an object so it won't encode as
        // an array; we don't cast the value here so the returned value is still
        // traversable as an array when accessed in PHP.
        'attributes' => $attributes_schema ?: new \ArrayObject(),
        'relationships' => $relationships_schema ?: new \ArrayObject(),
      ],
      'unevaluatedProperties' => FALSE,
    ];
  }

  protected function processConfigEntitySchema(ResourceType $resource_type, array $context, array &$attributes_schema): void {
    // This is largely the same as in ResourceObject but without a real entity.
    $fields = $resource_type->getFields();
    // Filter the array based on the field names.
    $enabled_field_names = array_filter(array_keys($fields), static fn (string $internal_field_name) => $resource_type->isFieldEnabled($internal_field_name));
    // Return a sub-array of $output containing the keys in $enabled_fields.
    $input = array_intersect_key($fields, array_flip($enabled_field_names));
    foreach ($input as $field_name => $field_value) {
      $attributes_schema['properties'][$resource_type->getPublicName($field_name)] = [
        'title' => $field_name,
        // @todo Potentially introspect schema to give more information.
        // @see https://www.drupal.org/project/drupal/issues/3426508
        // Right now, this will validate to anything.
      ];
    }
  }

  protected function processContentEntitySchema(ResourceObject $resource_object, array $context, array &$attributes_schema, &$relationships_schema): void {
    // Actual normalization supports sparse fieldsets, however we provide schema
    // for all possible fields that may be retrieved.
    $resource_type = $resource_object->getResourceType();
    $field_definitions = $this->entityFieldManager
      ->getFieldDefinitions($resource_type->getEntityTypeId(), $resource_type->getBundle());

    $resource_fields = $resource_type->getFields();
    // User resource objects contain a read-only attribute that is not a real
    // field on the user entity type.
    // @see \Drupal\jsonapi\JsonApiResource\ResourceObject::extractContentEntityFields()
    // @todo Eliminate this special casing in https://www.drupal.org/project/drupal/issues/3079254.
    if ($resource_type->getEntityTypeId() === 'user') {
      $resource_fields = array_diff_key($resource_fields, array_flip([$resource_type->getPublicName('display_name')]));
    }

    /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $fields */
    $fields = array_reduce(
      $resource_fields,
      function (array $carry, ResourceTypeField $resource_field) use ($field_definitions) {
        if (!$resource_field->isFieldEnabled()) {
          return $carry;
        }
        $carry[$resource_field->getPublicName()] = $field_definitions[$resource_field->getInternalName()];
        return $carry;
      },
      []
    );
    assert($this->serializer instanceof Serializer);
    $relationship_field_names = array_keys($resource_type->getRelatableResourceTypes());

    $create_values = [];
    if ($bundle_key = $this->entityTypeManager->getDefinition($resource_type->getEntityTypeId())->getKey('bundle')) {
      $create_values = [$bundle_key => $resource_type->getBundle()];
    }
    $stub_entity = $this->entityTypeManager
      ->getStorage($resource_type->getEntityTypeId())->create($create_values);
    foreach ($fields as $field_name => $field) {
      $stub_field = $stub_entity->get($field->getName());
      if ($stub_field instanceof EntityReferenceFieldItemListInterface) {
        // Build the relationship object based on the entity reference and
        // retrieve normalizer for that object instead.
        // @see ::serializeField()
        $relationship = Relationship::createFromEntityReferenceField($resource_object, $stub_field);
        $schema = $this->serializer->getJsonSchema($relationship, $context);
      }
      else {
        $schema = $this->serializer->getJsonSchema($stub_field, $context);
      }
      // Fallback basic annotations.
      if (empty($schema['title']) && $title = $field->getLabel()) {
        $schema['title'] = (string) $title;
      }
      if (empty($schema['description']) && $description = $field->getFieldStorageDefinition()->getDescription()) {
        $schema['description'] = (string) $description;
      }
      if (in_array($field_name, $relationship_field_names, TRUE)) {
        $relationships_schema['properties'][$field_name] = $schema;
      }
      else {
        $attributes_schema['properties'][$field_name] = $schema;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      ResourceObject::class => TRUE,
    ];
  }

}
