<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\jsonapi\JsonApiResource\ErrorCollection;
use Drupal\jsonapi\JsonApiResource\OmittedData;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiSpec;
use Drupal\jsonapi\Normalizer\Value\CacheableOmission;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;
use Drupal\jsonapi\ResourceType\ResourceType;
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
 * @see https://www.drupal.org/project/jsonapi/issues/3032787
 * @see jsonapi.api.php
 *
 * @see \Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel
 */
class JsonApiDocumentTopLevelNormalizer extends NormalizerBase implements DenormalizerInterface, NormalizerInterface {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = JsonApiDocumentTopLevel::class;

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
  public function denormalize($data, $class, $format = NULL, array $context = []) {
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
        catch (PluginNotFoundException $e) {
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
          $canonical_ids[] = $reference_item;
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
  public function normalize($object, $format = NULL, array $context = []) {
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
      // @todo: remove this if-else and just call $this->serializer->normalize($data...) in https://www.drupal.org/project/jsonapi/issues/3036285.
      if ($data instanceof EntityReferenceFieldItemListInterface) {
        $document['data'] = $this->normalizeEntityReferenceFieldItemList($object, $format, $context);
      }
      else {
        $document['data'] = $this->serializer->normalize($data, $format, $context);
      }
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
   * @todo: refactor this to use CacheableNormalization::aggregate in https://www.drupal.org/project/jsonapi/issues/3036284.
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
   * Normalizes an entity reference field, i.e. a relationship document.
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
   * @todo: remove this in https://www.drupal.org/project/jsonapi/issues/3036285.
   */
  protected function normalizeEntityReferenceFieldItemList(JsonApiDocumentTopLevel $document, $format, array $context = []) {
    $data = $document->getData();
    $parent_entity = $data->getEntity();
    $resource_type = $this->resourceTypeRepository->get($parent_entity->getEntityTypeId(), $parent_entity->bundle());
    $context['resource_object'] = ResourceObject::createFromEntity($resource_type, $parent_entity);
    $normalized_relationship = $this->serializer->normalize($data, $format, $context);
    assert($normalized_relationship instanceof CacheableNormalization);
    unset($context['resource_object']);
    return new CacheableNormalization($normalized_relationship, $normalized_relationship->getNormalization()['data']);
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
   * @todo: refactor this to use link collections in https://www.drupal.org/project/jsonapi/issues/3036279.
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
        $link_key = 'item:' . static::getLinkHash($link_hash_salt, $error['links']['via']['href']);
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

}
