<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\jsonapi\JsonApiResource\Data;
use Drupal\jsonapi\JsonApiResource\ErrorCollection;
use Drupal\jsonapi\JsonApiResource\IncludedData;
use Drupal\jsonapi\JsonApiResource\NullIncludedData;
use Drupal\jsonapi\JsonApiResource\OmittedData;
use Drupal\jsonapi\JsonApiResource\RelationshipData;
use Drupal\jsonapi\JsonApiResource\ResourceIdentifier;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\JsonApiSpec;
use Drupal\jsonapi\Normalizer\Value\CacheableOmission;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\serialization\Normalizer\SchematicNormalizerTrait;
use Drupal\serialization\Serializer\JsonSchemaProviderSerializerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;

/**
 * Normalizes the top-level document according to the JSON:API specification.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 *
 * @see \Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel
 */
class JsonApiDocumentTopLevelNormalizer extends NormalizerBase implements DenormalizerInterface, NormalizerInterface {

  use SchematicNormalizerTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The JSON:API resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * Constructs a JsonApiDocumentTopLevelNormalizer object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The JSON:API resource type repository.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ResourceTypeRepositoryInterface $resource_type_repository) {
    $this->entityTypeManager = $entity_type_manager;
    $this->resourceTypeRepository = $resource_type_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []): mixed {
    $resource_type = $context['resource_type'];

    // Validate a few common errors in document formatting.
    static::validateRequestBody($data, $resource_type);

    $normalized = [];

    if (!empty($data['data']['attributes'])) {
      $normalized = $data['data']['attributes'];
    }

    if (!empty($data['data']['id'])) {
      $uuid_key = $this->entityTypeManager->getDefinition($resource_type->getEntityTypeId())->getKey('uuid');
      $normalized[$uuid_key] = $data['data']['id'];
    }

    if (!empty($data['data']['relationships'])) {
      // Turn all single object relationship data fields into an array of
      // objects.
      $relationships = array_map(function ($relationship) {
        if (isset($relationship['data']['type']) && isset($relationship['data']['id'])) {
          return ['data' => [$relationship['data']]];
        }
        else {
          return $relationship;
        }
      }, $data['data']['relationships']);

      // Get an array of ids for every relationship.
      $relationships = array_map(function ($relationship) {
        if (empty($relationship['data'])) {
          return [];
        }
        if (empty($relationship['data'][0]['id'])) {
          throw new BadRequestHttpException("No ID specified for related resource");
        }
        $id_list = array_column($relationship['data'], 'id');
        if (empty($relationship['data'][0]['type'])) {
          throw new BadRequestHttpException("No type specified for related resource");
        }
        if (!$resource_type = $this->resourceTypeRepository->getByTypeName($relationship['data'][0]['type'])) {
          throw new BadRequestHttpException("Invalid type specified for related resource: '" . $relationship['data'][0]['type'] . "'");
        }

        $entity_type_id = $resource_type->getEntityTypeId();
        try {
          $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
        }
        catch (PluginNotFoundException) {
          throw new BadRequestHttpException("Invalid type specified for related resource: '" . $relationship['data'][0]['type'] . "'");
        }
        // In order to maintain the order ($delta) of the relationships, we need
        // to load the entities and create a mapping between id and uuid.
        $uuid_key = $this->entityTypeManager
          ->getDefinition($entity_type_id)->getKey('uuid');
        $related_entities = array_values($entity_storage->loadByProperties([$uuid_key => $id_list]));
        $map = [];
        foreach ($related_entities as $related_entity) {
          $map[$related_entity->uuid()] = $related_entity->id();
        }

        // $id_list has the correct order of uuids. We stitch this together with
        // $map which contains loaded entities, and then bring in the correct
        // meta values from the relationship, whose deltas match with $id_list.
        $canonical_ids = [];
        foreach ($id_list as $delta => $uuid) {
          if (!isset($map[$uuid])) {
            // @see \Drupal\jsonapi\Normalizer\EntityReferenceFieldNormalizer::normalize()
            if ($uuid === 'virtual') {
              continue;
            }
            throw new NotFoundHttpException(sprintf('The resource identified by `%s:%s` (given as a relationship item) could not be found.', $relationship['data'][$delta]['type'], $uuid));
          }
          $reference_item = [
            'target_id' => $map[$uuid],
          ];
          if (isset($relationship['data'][$delta]['meta'])) {
            $reference_item += $relationship['data'][$delta]['meta'];
          }
          $canonical_ids[] = array_filter($reference_item, function ($key) {
            return !str_starts_with($key, 'drupal_internal__');
          }, ARRAY_FILTER_USE_KEY);
        }

        return array_filter($canonical_ids);
      }, $relationships);

      // Add the relationship ids.
      $normalized = array_merge($normalized, $relationships);
    }
    // Override deserialization target class with the one in the ResourceType.
    $class = $context['resource_type']->getDeserializationTargetClass();

    return $this
      ->serializer
      ->denormalize($normalized, $class, $format, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function doNormalize($object, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    assert($object instanceof JsonApiDocumentTopLevel);
    $data = $object->getData();
    $document['jsonapi'] = CacheableNormalization::permanent([
      'version' => JsonApiSpec::SUPPORTED_SPECIFICATION_VERSION,
      'meta' => [
        'links' => [
          'self' => [
            'href' => JsonApiSpec::SUPPORTED_SPECIFICATION_PERMALINK,
          ],
        ],
      ],
    ]);
    if ($data instanceof ErrorCollection) {
      $document['errors'] = $this->normalizeErrorDocument($object, $format, $context);
    }
    else {
      // Add data.
      $document['data'] = $this->serializer->normalize($data, $format, $context);
      // Add includes.
      $document['included'] = $this->serializer->normalize($object->getIncludes(), $format, $context)->omitIfEmpty();
      // Add omissions and metadata.
      $normalized_omissions = $this->normalizeOmissionsLinks($object->getOmissions(), $format, $context);
      $meta = !$normalized_omissions instanceof CacheableOmission
        ? array_merge($object->getMeta(), ['omitted' => $normalized_omissions->getNormalization()])
        : $object->getMeta();
      $document['meta'] = (new CacheableNormalization($normalized_omissions, $meta))->omitIfEmpty();
    }
    // Add document links.
    $document['links'] = $this->serializer->normalize($object->getLinks(), $format, $context)->omitIfEmpty();
    // Every JSON:API document contains absolute URLs.
    return CacheableNormalization::aggregate($document)->withCacheableDependency((new CacheableMetadata())->addCacheContexts(['url.site']));
  }

  /**
   * Normalizes an error collection.
   *
   * @param \Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel $document
   *   The document to normalize.
   * @param string $format
   *   The normalization format.
   * @param array $context
   *   The normalization context.
   *
   * @return \Drupal\jsonapi\Normalizer\Value\CacheableNormalization
   *   The normalized document.
   *
   * @todo Refactor this to use CacheableNormalization::aggregate in https://www.drupal.org/project/drupal/issues/3036284.
   */
  protected function normalizeErrorDocument(JsonApiDocumentTopLevel $document, $format, array $context = []) {
    $normalized_values = array_map(function (HttpExceptionInterface $exception) use ($format, $context) {
      return $this->serializer->normalize($exception, $format, $context);
    }, (array) $document->getData()->getIterator());
    $cacheability = new CacheableMetadata();
    $errors = [];
    foreach ($normalized_values as $normalized_error) {
      $cacheability->addCacheableDependency($normalized_error);
      $errors = array_merge($errors, $normalized_error->getNormalization());
    }
    return new CacheableNormalization($cacheability, $errors);
  }

  /**
   * Normalizes omitted data into a set of omission links.
   *
   * @param \Drupal\jsonapi\JsonApiResource\OmittedData $omissions
   *   The omitted response data.
   * @param string $format
   *   The normalization format.
   * @param array $context
   *   The normalization context.
   *
   * @return \Drupal\jsonapi\Normalizer\Value\CacheableNormalization|\Drupal\jsonapi\Normalizer\Value\CacheableOmission
   *   The normalized omissions.
   *
   * @todo Refactor this to use link collections in https://www.drupal.org/project/drupal/issues/3036279.
   */
  protected function normalizeOmissionsLinks(OmittedData $omissions, $format, array $context = []) {
    $normalized_omissions = array_map(function (HttpExceptionInterface $exception) use ($format, $context) {
      return $this->serializer->normalize($exception, $format, $context);
    }, $omissions->toArray());
    $cacheability = CacheableMetadata::createFromObject(CacheableNormalization::aggregate($normalized_omissions));
    if (empty($normalized_omissions)) {
      return new CacheableOmission($cacheability);
    }
    $omission_links = [
      'detail' => 'Some resources have been omitted because of insufficient authorization.',
      'links' => [
        'help' => [
          'href' => 'https://www.drupal.org/docs/8/modules/json-api/filtering#filters-access-control',
        ],
      ],
    ];
    $link_hash_salt = Crypt::randomBytesBase64();
    foreach ($normalized_omissions as $omission) {
      $cacheability->addCacheableDependency($omission);
      // Add the errors to the pre-existing errors.
      foreach ($omission->getNormalization() as $error) {
        // JSON:API links cannot be arrays and the spec generally favors link
        // relation types as keys. 'item' is the right link relation type, but
        // we need multiple values. To do that, we generate a meaningless,
        // random value to use as a unique key. That value is a hash of a
        // random salt and the link href. This ensures that the key is non-
        // deterministic while letting use deduplicate the links by their
        // href. The salt is *not* used for any cryptographic reason.
        $link_key = 'item--' . static::getLinkHash($link_hash_salt, $error['links']['via']['href']);
        $omission_links['links'][$link_key] = [
          'href' => $error['links']['via']['href'],
          'meta' => [
            'rel' => 'item',
            'detail' => $error['detail'],
          ],
        ];
      }
    }
    return new CacheableNormalization($cacheability, $omission_links);
  }

  /**
   * Performs minimal validation of the document.
   */
  protected static function validateRequestBody(array $document, ResourceType $resource_type) {
    // Ensure that the relationships key was not placed in the top level.
    if (isset($document['relationships']) && !empty($document['relationships'])) {
      throw new BadRequestHttpException("Found \"relationships\" within the document's top level. The \"relationships\" key must be within resource object.");
    }
    // Ensure that the resource object contains the "type" key.
    if (!isset($document['data']['type'])) {
      throw new BadRequestHttpException("Resource object must include a \"type\".");
    }
    // Ensure that the client provided ID is a valid UUID.
    if (isset($document['data']['id']) && !Uuid::isValid($document['data']['id'])) {
      throw new UnprocessableEntityHttpException('IDs should be properly generated and formatted UUIDs as described in RFC 4122.');
    }
    // Ensure that no relationship fields are being set via the attributes
    // resource object member.
    if (isset($document['data']['attributes'])) {
      $received_attribute_field_names = array_keys($document['data']['attributes']);
      $relationship_field_names = array_keys($resource_type->getRelatableResourceTypes());
      if ($relationship_fields_sent_as_attributes = array_intersect($received_attribute_field_names, $relationship_field_names)) {
        throw new UnprocessableEntityHttpException(sprintf("The following relationship fields were provided as attributes: [ %s ]", implode(', ', $relationship_fields_sent_as_attributes)));
      }
    }
  }

  /**
   * Hashes an omitted link.
   *
   * @param string $salt
   *   A hash salt.
   * @param string $link_href
   *   The omitted link.
   *
   * @return string
   *   A 7 character hash.
   */
  protected static function getLinkHash($salt, $link_href) {
    return substr(str_replace(['-', '_'], '', Crypt::hashBase64($salt . $link_href)), 0, 7);
  }

  /**
   * {@inheritdoc}
   */
  public function getNormalizationSchema(mixed $object, array $context = []): array {
    // If we are providing a schema based only on an interface, we lack context
    // to provide anything more than a ref to the JSON:API top-level schema.
    $fallbackSchema = [
      '$ref' => JsonApiSpec::SUPPORTED_SPECIFICATION_JSON_SCHEMA,
    ];
    if (is_string($object)) {
      return $fallbackSchema;
    }
    assert($object instanceof JsonApiDocumentTopLevel);
    if ($object->getData() instanceof OmittedData) {
      // A top-level omitted data object is a bit weird but it will only contain
      // information in the 'links' property, so we can fall back.
      return $fallbackSchema;
    }
    $schema = [
      'allOf' => [
        ['$ref' => JsonApiSpec::SUPPORTED_SPECIFICATION_JSON_SCHEMA],
      ],
    ];

    // Top-level JSON:API documents may contain top-level data or an error
    // collection.
    $data = $object->getData();

    if ($data instanceof ErrorCollection) {
      // There's not much else to state here, because errors are a known schema.
      $schema['required'] = ['errors'];
    }

    // Relationship data - "resource identifier object(s)"
    if ($data instanceof RelationshipData) {
      if ($data->getCardinality() === 1) {
        $schema['properties']['data'] = [
          '$ref' => JsonApiSpec::SUPPORTED_SPECIFICATION_JSON_SCHEMA . '#/definitions/relationship',
        ];
      }
      else {
        $schema['properties']['data'] = [
          'type' => 'array',
          'items' => [
            '$ref' => JsonApiSpec::SUPPORTED_SPECIFICATION_JSON_SCHEMA . '#/definitions/relationship',
          ],
          'unevaluatedItems' => FALSE,
        ];
        if ($data->getCardinality() !== FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
          $schema['properties']['data']['maxContains'] = $data->getCardinality();
        }
        // We can't do a minContains with the data available in this context.
      }
    }

    if ($data instanceof IncludedData) {
      if ($data instanceof NullIncludedData) {
        $schema['properties']['included'] = [
          'type' => 'array',
          'maxContains' => 0,
        ];
      }
      else {
        $schema['properties']['included'] = [
          // 'included' member is always an array.
          'type' => 'array',
          'items' => [
            'oneOf' => $this->getSchemasForDataCollection($data->getData(), $context),
          ],
        ];
      }
    }

    if ($data instanceof ResourceObjectData) {
      if ($data->getCardinality() === 1) {
        assert($data->count() === 1);
        $schema['properties']['data'] = [
          'oneOf' => [
            ...$this->getSchemasForDataCollection($data->getData(), $context),
            ['type' => 'null'],
          ],
        ];
      }
      else {
        $schema['properties']['data'] = [
          'type' => 'array',
          'items' => [
            'oneOf' => $this->getSchemasForDataCollection($data->getData(), $context),
          ],
        ];
        if ($data->getCardinality() !== FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
          $schema['properties']['data']['maxContains'] = $data->getCardinality();
        }
      }
    }

    return $schema;
  }

  /**
   * Retrieve an array of schemas for the resource types in a data object.
   *
   * @param \Drupal\jsonapi\JsonApiResource\Data $data
   *   JSON:API data value objects.
   * @param array $context
   *   Normalization context.
   *
   * @return array
   *   Schemas for all types represented in the collection.
   */
  protected function getSchemasForDataCollection(Data $data, array $context): array {
    $schemas = [];
    if ($data->count() === 0) {
      return [
        // We lack sufficient information about if the data would be a
        // collection or a single resource, so allow either.
        ['type' => ['array', 'null']],
      ];
    }
    $members = $data->toArray();
    assert($this->serializer instanceof JsonSchemaProviderSerializerInterface);
    // Per the spec, data must either be comprised of a single instance or
    // collection of resource objects OR resource identifiers, but not both.
    foreach ($members as $member) {
      $resourceType = $member->getResourceType();
      if (array_key_exists($resourceType->getTypeName(), $schemas)) {
        continue;
      }
      $schemas[$resourceType->getTypeName()] = $member instanceof ResourceIdentifier
        ? [
          'allOf' => [
            ['$ref' => JsonApiSpec::SUPPORTED_SPECIFICATION_JSON_SCHEMA . '#/definitions/resourceIdentifier'],
          ],
          'properties' => [
            'type' => [
              'const' => $resourceType->getTypeName(),
            ],
          ],
        ]
        : $this->serializer->getJsonSchema($member, $context);
    }
    return array_values($schemas);
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      JsonApiDocumentTopLevel::class => TRUE,
    ];
  }

}
