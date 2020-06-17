<?php

namespace Drupal\jsonapi\Controller;

use Drupal\Component\Assertion\Inspector;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\jsonapi\Access\EntityAccessChecker;
use Drupal\jsonapi\Context\FieldResolver;
use Drupal\jsonapi\Entity\EntityValidationTrait;
use Drupal\jsonapi\Access\TemporaryQueryGuard;
use Drupal\jsonapi\Exception\EntityAccessDeniedHttpException;
use Drupal\jsonapi\IncludeResolver;
use Drupal\jsonapi\JsonApiResource\IncludedData;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\NullIncludedData;
use Drupal\jsonapi\JsonApiResource\Relationship;
use Drupal\jsonapi\JsonApiResource\ResourceIdentifier;
use Drupal\jsonapi\JsonApiResource\Link;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\JsonApiResource\TopLevelDataInterface;
use Drupal\jsonapi\Query\Filter;
use Drupal\jsonapi\Query\Sort;
use Drupal\jsonapi\Query\OffsetPage;
use Drupal\jsonapi\JsonApiResource\Data;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeField;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi\Revisions\ResourceVersionRouteEnhancer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Drupal\Core\Http\Exception\CacheableBadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Process all entity requests.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @see https://www.drupal.org/project/jsonapi/issues/3032787
 * @see jsonapi.api.php
 */
class EntityResource {

  use EntityValidationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * The resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The include resolver.
   *
   * @var \Drupal\jsonapi\IncludeResolver
   */
  protected $includeResolver;

  /**
   * The JSON:API entity access checker.
   *
   * @var \Drupal\jsonapi\Access\EntityAccessChecker
   */
  protected $entityAccessChecker;

  /**
   * The JSON:API field resolver.
   *
   * @var \Drupal\jsonapi\Context\FieldResolver
   */
  protected $fieldResolver;

  /**
   * The JSON:API serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface|\Symfony\Component\Serializer\Normalizer\DenormalizerInterface
   */
  protected $serializer;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Instantiates a EntityResource object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   The entity type field manager.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The JSON:API resource type repository.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\jsonapi\IncludeResolver $include_resolver
   *   The include resolver.
   * @param \Drupal\jsonapi\Access\EntityAccessChecker $entity_access_checker
   *   The JSON:API entity access checker.
   * @param \Drupal\jsonapi\Context\FieldResolver $field_resolver
   *   The JSON:API field resolver.
   * @param \Symfony\Component\Serializer\SerializerInterface|\Symfony\Component\Serializer\Normalizer\DenormalizerInterface $serializer
   *   The JSON:API serializer.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user account.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $field_manager, ResourceTypeRepositoryInterface $resource_type_repository, RendererInterface $renderer, EntityRepositoryInterface $entity_repository, IncludeResolver $include_resolver, EntityAccessChecker $entity_access_checker, FieldResolver $field_resolver, SerializerInterface $serializer, TimeInterface $time, AccountInterface $user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldManager = $field_manager;
    $this->resourceTypeRepository = $resource_type_repository;
    $this->renderer = $renderer;
    $this->entityRepository = $entity_repository;
    $this->includeResolver = $include_resolver;
    $this->entityAccessChecker = $entity_access_checker;
    $this->fieldResolver = $field_resolver;
    $this->serializer = $serializer;
    $this->time = $time;
    $this->user = $user;
  }

  /**
   * Gets the individual entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The loaded entity.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   *
   * @throws \Drupal\jsonapi\Exception\EntityAccessDeniedHttpException
   *   Thrown when access to the entity is not allowed.
   */
  public function getIndividual(EntityInterface $entity, Request $request) {
    $resource_object = $this->entityAccessChecker->getAccessCheckedResourceObject($entity);
    if ($resource_object instanceof EntityAccessDeniedHttpException) {
      throw $resource_object;
    }
    $primary_data = new ResourceObjectData([$resource_object], 1);
    $response = $this->buildWrappedResponse($primary_data, $request, $this->getIncludes($request, $primary_data));
    return $response;
  }

  /**
   * Creates an individual entity.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The JSON:API resource type for the request to be served.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\ConflictHttpException
   *   Thrown when the entity already exists.
   * @throws \Drupal\jsonapi\Exception\UnprocessableHttpEntityException
   *   Thrown when the entity does not pass validation.
   */
  public function createIndividual(ResourceType $resource_type, Request $request) {
    $parsed_entity = $this->deserialize($resource_type, $request, JsonApiDocumentTopLevel::class);

    if ($parsed_entity instanceof FieldableEntityInterface) {
      // Only check 'edit' permissions for fields that were actually submitted
      // by the user. Field access makes no distinction between 'create' and
      // 'update', so the 'edit' operation is used here.
      $document = Json::decode($request->getContent());
      $field_mapping = array_map(function (ResourceTypeField $field) {
        return $field->getPublicName();
      }, $resource_type->getFields());
      // User resource objects contain a read-only attribute that is not a
      // real field on the user entity type.
      // @see \Drupal\jsonapi\JsonApiResource\ResourceObject::extractContentEntityFields()
      // @todo: eliminate this special casing in https://www.drupal.org/project/drupal/issues/3079254.
      if ($resource_type->getEntityTypeId() === 'user') {
        $field_mapping = array_diff($field_mapping, [$resource_type->getPublicName('display_name')]);
      }
      foreach (['attributes', 'relationships'] as $data_member_name) {
        if (isset($document['data'][$data_member_name])) {
          foreach (array_intersect_key(array_flip($field_mapping), $document['data'][$data_member_name]) as $internal_field_name) {
            $field_access = $parsed_entity->get($internal_field_name)->access('edit', NULL, TRUE);
            if (!$field_access->isAllowed()) {
              $public_field_name = $field_mapping[$internal_field_name];
              throw new EntityAccessDeniedHttpException(NULL, $field_access, "/data/$data_member_name/$public_field_name", sprintf('The current user is not allowed to POST the selected field (%s).', $public_field_name));
            }
          }
        }
      }
    }

    static::validate($parsed_entity);

    // Return a 409 Conflict response in accordance with the JSON:API spec. See
    // http://jsonapi.org/format/#crud-creating-responses-409.
    if ($this->entityExists($parsed_entity)) {
      throw new ConflictHttpException('Conflict: Entity already exists.');
    }

    $parsed_entity->save();

    // Build response object.
    $resource_object = ResourceObject::createFromEntity($resource_type, $parsed_entity);
    $primary_data = new ResourceObjectData([$resource_object], 1);
    $response = $this->buildWrappedResponse($primary_data, $request, $this->getIncludes($request, $primary_data), 201);

    // According to JSON:API specification, when a new entity was created
    // we should send "Location" header to the frontend.
    if ($resource_type->isLocatable()) {
      $url = $resource_object->toUrl()->setAbsolute()->toString(TRUE);
      $response->addCacheableDependency($url);
      $response->headers->set('Location', $url->getGeneratedUrl());
    }

    // Return response object with updated headers info.
    return $response;
  }

  /**
   * Patches an individual entity.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The JSON:API resource type for the request to be served.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The loaded entity.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown when the selected entity does not match the id in th payload.
   * @throws \Drupal\jsonapi\Exception\UnprocessableHttpEntityException
   *   Thrown when the patched entity does not pass validation.
   */
  public function patchIndividual(ResourceType $resource_type, EntityInterface $entity, Request $request) {
    if ($entity instanceof RevisionableInterface && !($entity->isLatestRevision() && $entity->isDefaultRevision())) {
      throw new BadRequestHttpException('Updating a resource object that has a working copy is not yet supported. See https://www.drupal.org/project/jsonapi/issues/2795279.');
    }

    $parsed_entity = $this->deserialize($resource_type, $request, JsonApiDocumentTopLevel::class);

    $body = Json::decode($request->getContent());
    $data = $body['data'];
    if ($data['id'] != $entity->uuid()) {
      throw new BadRequestHttpException(sprintf(
        'The selected entity (%s) does not match the ID in the payload (%s).',
        $entity->uuid(),
        $data['id']
      ));
    }
    $data += ['attributes' => [], 'relationships' => []];
    $field_names = array_map([$resource_type, 'getInternalName'], array_merge(array_keys($data['attributes']), array_keys($data['relationships'])));

    // User resource objects contain a read-only attribute that is not a real
    // field on the user entity type.
    // @see \Drupal\jsonapi\JsonApiResource\ResourceObject::extractContentEntityFields()
    // @todo: eliminate this special casing in https://www.drupal.org/project/drupal/issues/3079254.
    if ($entity->getEntityTypeId() === 'user') {
      $field_names = array_diff($field_names, [$resource_type->getPublicName('display_name')]);
    }

    array_reduce($field_names, function (EntityInterface $destination, $field_name) use ($resource_type, $parsed_entity) {
      $this->updateEntityField($resource_type, $parsed_entity, $destination, $field_name);
      return $destination;
    }, $entity);

    static::validate($entity, $field_names);

    // Set revision data details for revisionable entities.
    if ($entity->getEntityType()->isRevisionable()) {
      if ($bundle_entity_type = $entity->getEntityType()->getBundleEntityType()) {
        $bundle_entity = $this->entityTypeManager->getStorage($bundle_entity_type)->load($entity->bundle());
        if ($bundle_entity instanceof RevisionableEntityBundleInterface) {
          $entity->setNewRevision($bundle_entity->shouldCreateNewRevision());
        }
      }
      if ($entity instanceof RevisionLogInterface && $entity->isNewRevision()) {
        $entity->setRevisionUserId($this->user->id());
        $entity->setRevisionCreationTime($this->time->getRequestTime());
      }
    }

    $entity->save();
    $primary_data = new ResourceObjectData([ResourceObject::createFromEntity($resource_type, $entity)], 1);
    return $this->buildWrappedResponse($primary_data, $request, $this->getIncludes($request, $primary_data));
  }

  /**
   * Deletes an individual entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The loaded entity.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   */
  public function deleteIndividual(EntityInterface $entity) {
    $entity->delete();
    return new ResourceResponse(NULL, 204);
  }

  /**
   * Gets the collection of entities.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The JSON:API resource type for the request to be served.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   *
   * @throws \Drupal\Core\Http\Exception\CacheableBadRequestHttpException
   *   Thrown when filtering on a config entity which does not support it.
   */
  public function getCollection(ResourceType $resource_type, Request $request) {
    // Instantiate the query for the filtering.
    $entity_type_id = $resource_type->getEntityTypeId();

    $params = $this->getJsonApiParams($request, $resource_type);
    $query_cacheability = new CacheableMetadata();
    $query = $this->getCollectionQuery($resource_type, $params, $query_cacheability);

    // If the request is for the latest revision, toggle it on entity query.
    if ($request->get(ResourceVersionRouteEnhancer::WORKING_COPIES_REQUESTED, FALSE)) {
      $query->latestRevision();
    }

    try {
      $results = $this->executeQueryInRenderContext(
        $query,
        $query_cacheability
      );
    }
    catch (\LogicException $e) {
      // Ensure good DX when an entity query involves a config entity type.
      // For example: getting users with a particular role, which is a config
      // entity type: https://www.drupal.org/project/jsonapi/issues/2959445.
      // @todo Remove the message parsing in https://www.drupal.org/project/drupal/issues/3028967.
      if (strpos($e->getMessage(), 'Getting the base fields is not supported for entity type') === 0) {
        preg_match('/entity type (.*)\./', $e->getMessage(), $matches);
        $config_entity_type_id = $matches[1];
        $cacheability = (new CacheableMetadata())->addCacheContexts(['url.path', 'url.query_args:filter']);
        throw new CacheableBadRequestHttpException($cacheability, sprintf("Filtering on config entities is not supported by Drupal's entity API. You tried to filter on a %s config entity.", $config_entity_type_id));
      }
      else {
        throw $e;
      }
    }

    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    // We request N+1 items to find out if there is a next page for the pager.
    // We may need to remove that extra item before loading the entities.
    $pager_size = $query->getMetaData('pager_size');
    if ($has_next_page = $pager_size < count($results)) {
      // Drop the last result.
      array_pop($results);
    }
    // Each item of the collection data contains an array with 'entity' and
    // 'access' elements.
    $collection_data = $this->loadEntitiesWithAccess($storage, $results, $request->get(ResourceVersionRouteEnhancer::WORKING_COPIES_REQUESTED, FALSE));
    $primary_data = new ResourceObjectData($collection_data);
    $primary_data->setHasNextPage($has_next_page);

    // Calculate all the results and pass into a JSON:API Data object.
    $count_query_cacheability = new CacheableMetadata();
    if ($resource_type->includeCount()) {
      $count_query = $this->getCollectionCountQuery($resource_type, $params, $count_query_cacheability);
      $total_results = $this->executeQueryInRenderContext(
        $count_query,
        $count_query_cacheability
      );

      $primary_data->setTotalCount($total_results);
    }

    $response = $this->respondWithCollection($primary_data, $this->getIncludes($request, $primary_data), $request, $resource_type, $params[OffsetPage::KEY_NAME]);

    $response->addCacheableDependency($query_cacheability);
    $response->addCacheableDependency($count_query_cacheability);
    $response->addCacheableDependency((new CacheableMetadata())
      ->addCacheContexts([
        'url.query_args:filter',
        'url.query_args:sort',
        'url.query_args:page',
      ]));

    if ($resource_type->isVersionable()) {
      $response->addCacheableDependency((new CacheableMetadata())->addCacheContexts([ResourceVersionRouteEnhancer::CACHE_CONTEXT]));
    }

    return $response;
  }

  /**
   * Executes the query in a render context, to catch bubbled cacheability.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query to execute to get the return results.
   * @param \Drupal\Core\Cache\CacheableMetadata $query_cacheability
   *   The value object to carry the query cacheability.
   *
   * @return int|array
   *   Returns an integer for count queries or an array of IDs. The values of
   *   the array are always entity IDs. The keys will be revision IDs if the
   *   entity supports revision and entity IDs if not.
   *
   * @see node_query_node_access_alter()
   * @see https://www.drupal.org/project/drupal/issues/2557815
   * @see https://www.drupal.org/project/drupal/issues/2794385
   * @todo Remove this after https://www.drupal.org/project/drupal/issues/3028976 is fixed.
   */
  protected function executeQueryInRenderContext(QueryInterface $query, CacheableMetadata $query_cacheability) {
    $context = new RenderContext();
    $results = $this->renderer->executeInRenderContext($context, function () use ($query) {
      return $query->execute();
    });
    if (!$context->isEmpty()) {
      $query_cacheability->addCacheableDependency($context->pop());
    }
    return $results;
  }

  /**
   * Gets the related resource.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The JSON:API resource type for the request to be served.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The requested entity.
   * @param string $related
   *   The related field name.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   */
  public function getRelated(ResourceType $resource_type, FieldableEntityInterface $entity, $related, Request $request) {
    /* @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field_list */
    $resource_relationship = $resource_type->getFieldByPublicName($related);
    $field_list = $entity->get($resource_relationship->getInternalName());

    // Remove the entities pointing to a resource that may be disabled. Even
    // though the normalizer skips disabled references, we can avoid unnecessary
    // work by checking here too.
    /* @var \Drupal\Core\Entity\EntityInterface[] $referenced_entities */
    $referenced_entities = array_filter(
      $field_list->referencedEntities(),
      function (EntityInterface $entity) {
        return (bool) $this->resourceTypeRepository->get(
          $entity->getEntityTypeId(),
          $entity->bundle()
        );
      }
    );
    $collection_data = [];
    foreach ($referenced_entities as $referenced_entity) {
      $collection_data[] = $this->entityAccessChecker->getAccessCheckedResourceObject($referenced_entity);
    }
    $primary_data = new ResourceObjectData($collection_data, $resource_relationship->hasOne() ? 1 : -1);
    $response = $this->buildWrappedResponse($primary_data, $request, $this->getIncludes($request, $primary_data));

    // $response does not contain the entity list cache tag. We add the
    // cacheable metadata for the finite list of entities in the relationship.
    $response->addCacheableDependency($entity);

    return $response;
  }

  /**
   * Gets the relationship of an entity.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The base JSON:API resource type for the request to be served.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The requested entity.
   * @param string $related
   *   The related field name.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param int $response_code
   *   The response code. Defaults to 200.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   */
  public function getRelationship(ResourceType $resource_type, FieldableEntityInterface $entity, $related, Request $request, $response_code = 200) {
    /* @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field_list */
    $field_list = $entity->get($resource_type->getInternalName($related));
    // Access will have already been checked by the RelationshipFieldAccess
    // service, so we don't need to call ::getAccessCheckedResourceObject().
    $resource_object = ResourceObject::createFromEntity($resource_type, $entity);
    $relationship = Relationship::createFromEntityReferenceField($resource_object, $field_list);
    $response = $this->buildWrappedResponse($relationship, $request, $this->getIncludes($request, $resource_object), $response_code);
    // Add the host entity as a cacheable dependency.
    $response->addCacheableDependency($entity);
    return $response;
  }

  /**
   * Adds a relationship to a to-many relationship.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The base JSON:API resource type for the request to be served.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The requested entity.
   * @param string $related
   *   The related field name.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   *
   * @throws \Drupal\jsonapi\Exception\EntityAccessDeniedHttpException
   *   Thrown when the current user is not allowed to PATCH the selected
   *   field(s).
   * @throws \Symfony\Component\HttpKernel\Exception\ConflictHttpException
   *   Thrown when POSTing to a "to-one" relationship.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when the underlying entity cannot be saved.
   * @throws \Drupal\jsonapi\Exception\UnprocessableHttpEntityException
   *   Thrown when the updated entity does not pass validation.
   */
  public function addToRelationshipData(ResourceType $resource_type, FieldableEntityInterface $entity, $related, Request $request) {
    $resource_identifiers = $this->deserialize($resource_type, $request, ResourceIdentifier::class, $related);
    $internal_relationship_field_name = $resource_type->getInternalName($related);
    // According to the specification, you are only allowed to POST to a
    // relationship if it is a to-many relationship.
    /* @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field_list */
    $field_list = $entity->{$internal_relationship_field_name};
    /* @var \Drupal\field\Entity\FieldConfig $field_definition */
    $field_definition = $field_list->getFieldDefinition();
    $is_multiple = $field_definition->getFieldStorageDefinition()->isMultiple();
    if (!$is_multiple) {
      throw new ConflictHttpException(sprintf('You can only POST to to-many relationships. %s is a to-one relationship.', $related));
    }

    $original_resource_identifiers = ResourceIdentifier::toResourceIdentifiersWithArityRequired($field_list);
    $new_resource_identifiers = array_udiff(
      ResourceIdentifier::deduplicate(array_merge($original_resource_identifiers, $resource_identifiers)),
      $original_resource_identifiers,
      [ResourceIdentifier::class, 'compare']
    );

    // There are no relationships that need to be added so we can exit early.
    if (empty($new_resource_identifiers)) {
      $status = static::relationshipResponseRequiresBody($resource_identifiers, $original_resource_identifiers) ? 200 : 204;
      return $this->getRelationship($resource_type, $entity, $related, $request, $status);
    }

    $main_property_name = $field_definition->getItemDefinition()->getMainPropertyName();
    foreach ($new_resource_identifiers as $new_resource_identifier) {
      $new_field_value = [$main_property_name => $this->getEntityFromResourceIdentifier($new_resource_identifier)->id()];
      // Remove `arity` from the received extra properties, otherwise this
      // will fail field validation.
      $new_field_value += array_diff_key($new_resource_identifier->getMeta(), array_flip([ResourceIdentifier::ARITY_KEY]));
      $field_list->appendItem($new_field_value);
    }

    $this->validate($entity);
    $entity->save();

    $final_resource_identifiers = ResourceIdentifier::toResourceIdentifiersWithArityRequired($field_list);
    $status = static::relationshipResponseRequiresBody($resource_identifiers, $final_resource_identifiers) ? 200 : 204;
    return $this->getRelationship($resource_type, $entity, $related, $request, $status);
  }

  /**
   * Updates the relationship of an entity.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The base JSON:API resource type for the request to be served.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The requested entity.
   * @param string $related
   *   The related field name.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when the underlying entity cannot be saved.
   * @throws \Drupal\jsonapi\Exception\UnprocessableHttpEntityException
   *   Thrown when the updated entity does not pass validation.
   */
  public function replaceRelationshipData(ResourceType $resource_type, EntityInterface $entity, $related, Request $request) {
    $resource_identifiers = $this->deserialize($resource_type, $request, ResourceIdentifier::class, $related);
    $internal_relationship_field_name = $resource_type->getInternalName($related);
    /* @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $resource_identifiers */
    // According to the specification, PATCH works a little bit different if the
    // relationship is to-one or to-many.
    /* @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field_list */
    $field_list = $entity->{$internal_relationship_field_name};
    $field_definition = $field_list->getFieldDefinition();
    $is_multiple = $field_definition->getFieldStorageDefinition()->isMultiple();
    $method = $is_multiple ? 'doPatchMultipleRelationship' : 'doPatchIndividualRelationship';
    $this->{$method}($entity, $resource_identifiers, $field_definition);
    $this->validate($entity);
    $entity->save();
    $requires_response = static::relationshipResponseRequiresBody($resource_identifiers, ResourceIdentifier::toResourceIdentifiersWithArityRequired($field_list));
    return $this->getRelationship($resource_type, $entity, $related, $request, $requires_response ? 200 : 204);
  }

  /**
   * Update a to-one relationship.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The requested entity.
   * @param \Drupal\jsonapi\JsonApiResource\ResourceIdentifier[] $resource_identifiers
   *   The client-sent resource identifiers which should be set on the given
   *   entity. Should be an empty array or an array with a single value.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition of the entity field to be updated.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown when a "to-one" relationship is not provided.
   */
  protected function doPatchIndividualRelationship(EntityInterface $entity, array $resource_identifiers, FieldDefinitionInterface $field_definition) {
    if (count($resource_identifiers) > 1) {
      throw new BadRequestHttpException(sprintf('Provide a single relationship so to-one relationship fields (%s).', $field_definition->getName()));
    }
    $this->doPatchMultipleRelationship($entity, $resource_identifiers, $field_definition);
  }

  /**
   * Update a to-many relationship.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The requested entity.
   * @param \Drupal\jsonapi\JsonApiResource\ResourceIdentifier[] $resource_identifiers
   *   The client-sent resource identifiers which should be set on the given
   *   entity.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition of the entity field to be updated.
   */
  protected function doPatchMultipleRelationship(EntityInterface $entity, array $resource_identifiers, FieldDefinitionInterface $field_definition) {
    $main_property_name = $field_definition->getItemDefinition()->getMainPropertyName();
    $entity->{$field_definition->getName()} = array_map(function (ResourceIdentifier $resource_identifier) use ($main_property_name) {
      $field_properties = [$main_property_name => $this->getEntityFromResourceIdentifier($resource_identifier)->id()];
      // Remove `arity` from the received extra properties, otherwise this
      // will fail field validation.
      $field_properties += array_diff_key($resource_identifier->getMeta(), array_flip([ResourceIdentifier::ARITY_KEY]));
      return $field_properties;
    }, $resource_identifiers);
  }

  /**
   * Deletes the relationship of an entity.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The base JSON:API resource type for the request to be served.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The requested entity.
   * @param string $related
   *   The related field name.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown when not body was provided for the DELETE operation.
   * @throws \Symfony\Component\HttpKernel\Exception\ConflictHttpException
   *   Thrown when deleting a "to-one" relationship.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when the underlying entity cannot be saved.
   */
  public function removeFromRelationshipData(ResourceType $resource_type, EntityInterface $entity, $related, Request $request) {
    $resource_identifiers = $this->deserialize($resource_type, $request, ResourceIdentifier::class, $related);
    $internal_relationship_field_name = $resource_type->getInternalName($related);
    /* @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field_list */
    $field_list = $entity->{$internal_relationship_field_name};
    $is_multiple = $field_list->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->isMultiple();
    if (!$is_multiple) {
      throw new ConflictHttpException(sprintf('You can only DELETE from to-many relationships. %s is a to-one relationship.', $related));
    }

    // Compute the list of current values and remove the ones in the payload.
    $original_resource_identifiers = ResourceIdentifier::toResourceIdentifiersWithArityRequired($field_list);
    $removed_resource_identifiers = array_uintersect($resource_identifiers, $original_resource_identifiers, [ResourceIdentifier::class, 'compare']);
    $deltas_to_be_removed = [];
    foreach ($removed_resource_identifiers as $removed_resource_identifier) {
      foreach ($original_resource_identifiers as $delta => $existing_resource_identifier) {
        // Identify the field item deltas which should be removed.
        if (ResourceIdentifier::isDuplicate($removed_resource_identifier, $existing_resource_identifier)) {
          $deltas_to_be_removed[] = $delta;
        }
      }
    }
    // Field item deltas are reset when an item is removed. This removes
    // items in descending order so that the deltas yet to be removed will
    // continue to exist.
    rsort($deltas_to_be_removed);
    foreach ($deltas_to_be_removed as $delta) {
      $field_list->removeItem($delta);
    }

    // Save the entity and return the response object.
    static::validate($entity);
    $entity->save();
    return $this->getRelationship($resource_type, $entity, $related, $request, 204);
  }

  /**
   * Deserializes a request body, if any.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The JSON:API resource type for the current request.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string $class
   *   The class into which the request data needs to be deserialized.
   * @param string $relationship_field_name
   *   The public relationship field name of the data to be deserialized if the
   *   incoming request is for a relationship update. Not required for non-
   *   relationship requests.
   *
   * @return array
   *   An object normalization.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown if the request body cannot be decoded, or when no request body was
   *   provided with a POST or PATCH request.
   * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
   *   Thrown if the request body cannot be denormalized.
   */
  protected function deserialize(ResourceType $resource_type, Request $request, $class, $relationship_field_name = NULL) {
    assert($class === JsonApiDocumentTopLevel::class || $class === ResourceIdentifier::class && !empty($relationship_field_name) && is_string($relationship_field_name));
    $received = (string) $request->getContent();
    if (!$received) {
      assert($request->isMethod('POST') || $request->isMethod('PATCH') || $request->isMethod('DELETE'));
      if ($request->isMethod('DELETE') && $relationship_field_name) {
        throw new BadRequestHttpException(sprintf('You need to provide a body for DELETE operations on a relationship (%s).', $relationship_field_name));
      }
      else {
        throw new BadRequestHttpException('Empty request body.');
      }
    }
    // First decode the request data. We can then determine if the serialized
    // data was malformed.
    try {
      $decoded = $this->serializer->decode($received, 'api_json');
    }
    catch (UnexpectedValueException $e) {
      // If an exception was thrown at this stage, there was a problem decoding
      // the data. Throw a 400 HTTP exception.
      throw new BadRequestHttpException($e->getMessage());
    }

    try {
      $context = ['resource_type' => $resource_type];
      if ($relationship_field_name) {
        $context['related'] = $resource_type->getInternalName($relationship_field_name);
      }
      return $this->serializer->denormalize($decoded, $class, 'api_json', $context);
    }
    // These two serialization exception types mean there was a problem with
    // the structure of the decoded data and it's not valid.
    catch (UnexpectedValueException $e) {
      throw new UnprocessableEntityHttpException($e->getMessage());
    }
    catch (InvalidArgumentException $e) {
      throw new UnprocessableEntityHttpException($e->getMessage());
    }
  }

  /**
   * Gets a basic query for a collection.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The base JSON:API resource type for the query.
   * @param array $params
   *   The parameters for the query.
   * @param \Drupal\Core\Cache\CacheableMetadata $query_cacheability
   *   Collects cacheability for the query.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   A new query.
   */
  protected function getCollectionQuery(ResourceType $resource_type, array $params, CacheableMetadata $query_cacheability) {
    $entity_type = $this->entityTypeManager->getDefinition($resource_type->getEntityTypeId());
    $entity_storage = $this->entityTypeManager->getStorage($resource_type->getEntityTypeId());

    $query = $entity_storage->getQuery();

    // Ensure that access checking is performed on the query.
    $query->accessCheck(TRUE);

    // Compute and apply an entity query condition from the filter parameter.
    if (isset($params[Filter::KEY_NAME]) && $filter = $params[Filter::KEY_NAME]) {
      $query->condition($filter->queryCondition($query));
      TemporaryQueryGuard::setFieldManager($this->fieldManager);
      TemporaryQueryGuard::setModuleHandler(\Drupal::moduleHandler());
      TemporaryQueryGuard::applyAccessControls($filter, $query, $query_cacheability);
    }

    // Apply any sorts to the entity query.
    if (isset($params[Sort::KEY_NAME]) && $sort = $params[Sort::KEY_NAME]) {
      foreach ($sort->fields() as $field) {
        $path = $this->fieldResolver->resolveInternalEntityQueryPath($resource_type, $field[Sort::PATH_KEY]);
        $direction = isset($field[Sort::DIRECTION_KEY]) ? $field[Sort::DIRECTION_KEY] : 'ASC';
        $langcode = isset($field[Sort::LANGUAGE_KEY]) ? $field[Sort::LANGUAGE_KEY] : NULL;
        $query->sort($path, $direction, $langcode);
      }
    }

    // Apply any pagination options to the query.
    if (isset($params[OffsetPage::KEY_NAME])) {
      $pagination = $params[OffsetPage::KEY_NAME];
    }
    else {
      $pagination = new OffsetPage(OffsetPage::DEFAULT_OFFSET, OffsetPage::SIZE_MAX);
    }
    // Add one extra element to the page to see if there are more pages needed.
    $query->range($pagination->getOffset(), $pagination->getSize() + 1);
    $query->addMetaData('pager_size', (int) $pagination->getSize());

    // Limit this query to the bundle type for this resource.
    $bundle = $resource_type->getBundle();
    if ($bundle && ($bundle_key = $entity_type->getKey('bundle'))) {
      $query->condition(
        $bundle_key, $bundle
      );
    }

    return $query;
  }

  /**
   * Gets a basic query for a collection count.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The base JSON:API resource type for the query.
   * @param array $params
   *   The parameters for the query.
   * @param \Drupal\Core\Cache\CacheableMetadata $query_cacheability
   *   Collects cacheability for the query.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   A new query.
   */
  protected function getCollectionCountQuery(ResourceType $resource_type, array $params, CacheableMetadata $query_cacheability) {
    // Reset the range to get all the available results.
    return $this->getCollectionQuery($resource_type, $params, $query_cacheability)->range()->count();
  }

  /**
   * Loads the entity targeted by a resource identifier.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceIdentifier $resource_identifier
   *   A resource identifier.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity targeted by a resource identifier.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown if the given resource identifier targets a resource type or
   *   resource which does not exist.
   */
  protected function getEntityFromResourceIdentifier(ResourceIdentifier $resource_identifier) {
    $resource_type_name = $resource_identifier->getTypeName();
    if (!($target_resource_type = $this->resourceTypeRepository->getByTypeName($resource_type_name))) {
      throw new BadRequestHttpException("The resource type `{$resource_type_name}` does not exist.");
    }
    $id = $resource_identifier->getId();
    if (!($targeted_resource = $this->entityRepository->loadEntityByUuid($target_resource_type->getEntityTypeId(), $id))) {
      throw new BadRequestHttpException("The targeted `{$resource_type_name}` resource with ID `{$id}` does not exist.");
    }
    return $targeted_resource;
  }

  /**
   * Determines if the client needs to be updated with new relationship data.
   *
   * @param array $received_resource_identifiers
   *   The array of resource identifiers given by the client.
   * @param array $final_resource_identifiers
   *   The final array of resource identifiers after applying the requested
   *   changes.
   *
   * @return bool
   *   Whether the final array of resource identifiers is different than the
   *   client-sent data.
   */
  protected static function relationshipResponseRequiresBody(array $received_resource_identifiers, array $final_resource_identifiers) {
    return !empty(array_udiff($final_resource_identifiers, $received_resource_identifiers, [ResourceIdentifier::class, 'compare']));
  }

  /**
   * Builds a response with the appropriate wrapped document.
   *
   * @param \Drupal\jsonapi\JsonApiResource\TopLevelDataInterface $data
   *   The data to wrap.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\jsonapi\JsonApiResource\IncludedData $includes
   *   The resources to be included in the document. Use NullData if
   *   there should be no included resources in the document.
   * @param int $response_code
   *   The response code.
   * @param array $headers
   *   An array of response headers.
   * @param \Drupal\jsonapi\JsonApiResource\LinkCollection $links
   *   The URLs to which to link. A 'self' link is added automatically.
   * @param array $meta
   *   (optional) The top-level metadata.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   */
  protected function buildWrappedResponse(TopLevelDataInterface $data, Request $request, IncludedData $includes, $response_code = 200, array $headers = [], LinkCollection $links = NULL, array $meta = []) {
    $links = ($links ?: new LinkCollection([]));
    if (!$links->hasLinkWithKey('self')) {
      $self_link = new Link(new CacheableMetadata(), self::getRequestLink($request), 'self');
      $links = $links->withLink('self', $self_link);
    }
    $response = new ResourceResponse(new JsonApiDocumentTopLevel($data, $includes, $links, $meta), $response_code, $headers);
    $cacheability = (new CacheableMetadata())->addCacheContexts([
      // Make sure that different sparse fieldsets are cached differently.
      'url.query_args:fields',
      // Make sure that different sets of includes are cached differently.
      'url.query_args:include',
    ]);
    $response->addCacheableDependency($cacheability);
    return $response;
  }

  /**
   * Respond with an entity collection.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObjectData $primary_data
   *   The collection of entities.
   * @param \Drupal\jsonapi\JsonApiResource\IncludedData|\Drupal\jsonapi\JsonApiResource\NullIncludedData $includes
   *   The resources to be included in the document.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The base JSON:API resource type for the request to be served.
   * @param \Drupal\jsonapi\Query\OffsetPage $page_param
   *   The pagination parameter for the requested collection.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   */
  protected function respondWithCollection(ResourceObjectData $primary_data, Data $includes, Request $request, ResourceType $resource_type, OffsetPage $page_param) {
    assert(Inspector::assertAllObjects([$includes], IncludedData::class, NullIncludedData::class));
    $link_context = [
      'has_next_page' => $primary_data->hasNextPage(),
    ];
    $meta = [];
    if ($resource_type->includeCount()) {
      $link_context['total_count'] = $meta['count'] = $primary_data->getTotalCount();
    }
    $collection_links = self::getPagerLinks($request, $page_param, $link_context);
    $response = $this->buildWrappedResponse($primary_data, $request, $includes, 200, [], $collection_links, $meta);

    // When a new change to any entity in the resource happens, we cannot ensure
    // the validity of this cached list. Add the list tag to deal with that.
    $list_tag = $this->entityTypeManager->getDefinition($resource_type->getEntityTypeId())
      ->getListCacheTags();
    $response->getCacheableMetadata()->addCacheTags($list_tag);
    foreach ($primary_data as $entity) {
      $response->addCacheableDependency($entity);
    }
    return $response;
  }

  /**
   * Takes a field from the origin entity and puts it to the destination entity.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The JSON:API resource type of the entity to be updated.
   * @param \Drupal\Core\Entity\EntityInterface $origin
   *   The entity that contains the field values.
   * @param \Drupal\Core\Entity\EntityInterface $destination
   *   The entity that needs to be updated.
   * @param string $field_name
   *   The name of the field to extract and update.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown when the serialized and destination entities are of different
   *   types.
   */
  protected function updateEntityField(ResourceType $resource_type, EntityInterface $origin, EntityInterface $destination, $field_name) {
    // The update is different for configuration entities and content entities.
    if ($origin instanceof ContentEntityInterface && $destination instanceof ContentEntityInterface) {
      // First scenario: both are content entities.
      $field_name = $resource_type->getInternalName($field_name);
      $destination_field_list = $destination->get($field_name);

      $origin_field_list = $origin->get($field_name);
      if ($this->checkPatchFieldAccess($destination_field_list, $origin_field_list)) {
        $destination->set($field_name, $origin_field_list->getValue());
      }
    }
    elseif ($origin instanceof ConfigEntityInterface && $destination instanceof ConfigEntityInterface) {
      // Second scenario: both are config entities.
      $destination->set($field_name, $origin->get($field_name));
    }
    else {
      throw new BadRequestHttpException('The serialized entity and the destination entity are of different types.');
    }
  }

  /**
   * Gets includes for the given response data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObject|\Drupal\jsonapi\JsonApiResource\ResourceObjectData $data
   *   The response data from which to resolve includes.
   *
   * @return \Drupal\jsonapi\JsonApiResource\Data
   *   A Data object to be included or a NullData object if the request does not
   *   specify any include paths.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getIncludes(Request $request, $data) {
    assert($data instanceof ResourceObject || $data instanceof ResourceObjectData);
    return $request->query->has('include') && ($include_parameter = $request->query->get('include')) && !empty($include_parameter)
      ? $this->includeResolver->resolve($data, $include_parameter)
      : new NullIncludedData();
  }

  /**
   * Checks whether the given field should be PATCHed.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $original_field
   *   The original (stored) value for the field.
   * @param \Drupal\Core\Field\FieldItemListInterface $received_field
   *   The received value for the field.
   *
   * @return bool
   *   Whether the field should be PATCHed or not.
   *
   * @throws \Drupal\jsonapi\Exception\EntityAccessDeniedHttpException
   *   Thrown when the user sending the request is not allowed to update the
   *   field. Only thrown when the user could not abuse this information to
   *   determine the stored value.
   *
   * @internal
   *
   * @see \Drupal\rest\Plugin\rest\resource\EntityResource::checkPatchFieldAccess()
   */
  protected function checkPatchFieldAccess(FieldItemListInterface $original_field, FieldItemListInterface $received_field) {
    // If the user is allowed to edit the field, it is always safe to set the
    // received value. We may be setting an unchanged value, but that is ok.
    $field_edit_access = $original_field->access('edit', NULL, TRUE);
    if ($field_edit_access->isAllowed()) {
      return TRUE;
    }

    // The user might not have access to edit the field, but still needs to
    // submit the current field value as part of the PATCH request. For
    // example, the entity keys required by denormalizers. Therefore, if the
    // received value equals the stored value, return FALSE without throwing an
    // exception. But only for fields that the user has access to view, because
    // the user has no legitimate way of knowing the current value of fields
    // that they are not allowed to view, and we must not make the presence or
    // absence of a 403 response a way to find that out.
    if ($original_field->access('view') && $original_field->equals($received_field)) {
      return FALSE;
    }

    // It's helpful and safe to let the user know when they are not allowed to
    // update a field.
    $field_name = $received_field->getName();
    throw new EntityAccessDeniedHttpException($original_field->getEntity(), $field_edit_access, '/data/attributes/' . $field_name, sprintf('The current user is not allowed to PATCH the selected field (%s).', $field_name));
  }

  /**
   * Build a collection of the entities to respond with and access objects.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage to load the entities from.
   * @param int[] $ids
   *   An array of entity IDs, keyed by revision ID if the entity type is
   *   revisionable.
   * @param bool $load_latest_revisions
   *   Whether to load the latest revisions instead of the defaults.
   *
   * @return array
   *   An array of loaded entities and/or an access exceptions.
   */
  protected function loadEntitiesWithAccess(EntityStorageInterface $storage, array $ids, $load_latest_revisions) {
    $output = [];
    if ($load_latest_revisions) {
      assert($storage instanceof RevisionableStorageInterface);
      $entities = $storage->loadMultipleRevisions(array_keys($ids));
    }
    else {
      $entities = $storage->loadMultiple($ids);
    }
    foreach ($entities as $entity) {
      $output[$entity->id()] = $this->entityAccessChecker->getAccessCheckedResourceObject($entity);
    }
    return array_values($output);
  }

  /**
   * Checks if the given entity exists.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to test existence.
   *
   * @return bool
   *   Whether the entity already has been created.
   */
  protected function entityExists(EntityInterface $entity) {
    $entity_storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    return !empty($entity_storage->loadByProperties([
      'uuid' => $entity->uuid(),
    ]));
  }

  /**
   * Extracts JSON:API query parameters from the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The JSON:API resource type.
   *
   * @return array
   *   An array of JSON:API parameters like `sort` and `filter`.
   */
  protected function getJsonApiParams(Request $request, ResourceType $resource_type) {
    if ($request->query->has('filter')) {
      $params[Filter::KEY_NAME] = Filter::createFromQueryParameter($request->query->get('filter'), $resource_type, $this->fieldResolver);
    }
    if ($request->query->has('sort')) {
      $params[Sort::KEY_NAME] = Sort::createFromQueryParameter($request->query->get('sort'));
    }
    if ($request->query->has('page')) {
      $params[OffsetPage::KEY_NAME] = OffsetPage::createFromQueryParameter($request->query->get('page'));
    }
    else {
      $params[OffsetPage::KEY_NAME] = OffsetPage::createFromQueryParameter(['page' => ['offset' => OffsetPage::DEFAULT_OFFSET, 'limit' => OffsetPage::SIZE_MAX]]);
    }
    return $params;
  }

  /**
   * Get the full URL for a given request object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param array|null $query
   *   The query parameters to use. Leave it empty to get the query from the
   *   request object.
   *
   * @return \Drupal\Core\Url
   *   The full URL.
   */
  protected static function getRequestLink(Request $request, $query = NULL) {
    if ($query === NULL) {
      return Url::fromUri($request->getUri());
    }

    $uri_without_query_string = $request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo();
    return Url::fromUri($uri_without_query_string)->setOption('query', $query);
  }

  /**
   * Get the pager links for a given request object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\jsonapi\Query\OffsetPage $page_param
   *   The current pagination parameter for the requested collection.
   * @param array $link_context
   *   An associative array with extra data to build the links.
   *
   * @return \Drupal\jsonapi\JsonApiResource\LinkCollection
   *   An LinkCollection, with:
   *   - a 'next' key if it is not the last page;
   *   - 'prev' and 'first' keys if it's not the first page.
   */
  protected static function getPagerLinks(Request $request, OffsetPage $page_param, array $link_context = []) {
    $pager_links = new LinkCollection([]);
    if (!empty($link_context['total_count']) && !$total = (int) $link_context['total_count']) {
      return $pager_links;
    }
    /* @var \Drupal\jsonapi\Query\OffsetPage $page_param */
    $offset = $page_param->getOffset();
    $size = $page_param->getSize();
    if ($size <= 0) {
      $cacheability = (new CacheableMetadata())->addCacheContexts(['url.query_args:page']);
      throw new CacheableBadRequestHttpException($cacheability, sprintf('The page size needs to be a positive integer.'));
    }
    $query = (array) $request->query->getIterator();
    // Check if this is not the last page.
    if ($link_context['has_next_page']) {
      $next_url = static::getRequestLink($request, static::getPagerQueries('next', $offset, $size, $query));
      $pager_links = $pager_links->withLink('next', new Link(new CacheableMetadata(), $next_url, 'next'));

      if (!empty($total)) {
        $last_url = static::getRequestLink($request, static::getPagerQueries('last', $offset, $size, $query, $total));
        $pager_links = $pager_links->withLink('last', new Link(new CacheableMetadata(), $last_url, 'last'));
      }
    }

    // Check if this is not the first page.
    if ($offset > 0) {
      $first_url = static::getRequestLink($request, static::getPagerQueries('first', $offset, $size, $query));
      $pager_links = $pager_links->withLink('first', new Link(new CacheableMetadata(), $first_url, 'first'));
      $prev_url = static::getRequestLink($request, static::getPagerQueries('prev', $offset, $size, $query));
      $pager_links = $pager_links->withLink('prev', new Link(new CacheableMetadata(), $prev_url, 'prev'));
    }

    return $pager_links;
  }

  /**
   * Get the query param array.
   *
   * @param string $link_id
   *   The name of the pagination link requested.
   * @param int $offset
   *   The starting index.
   * @param int $size
   *   The pagination page size.
   * @param array $query
   *   The query parameters.
   * @param int $total
   *   The total size of the collection.
   *
   * @return array
   *   The pagination query param array.
   */
  protected static function getPagerQueries($link_id, $offset, $size, array $query = [], $total = 0) {
    $extra_query = [];
    switch ($link_id) {
      case 'next':
        $extra_query = [
          'page' => [
            'offset' => $offset + $size,
            'limit' => $size,
          ],
        ];
        break;

      case 'first':
        $extra_query = [
          'page' => [
            'offset' => 0,
            'limit' => $size,
          ],
        ];
        break;

      case 'last':
        if ($total) {
          $extra_query = [
            'page' => [
              'offset' => (ceil($total / $size) - 1) * $size,
              'limit' => $size,
            ],
          ];
        }
        break;

      case 'prev':
        $extra_query = [
          'page' => [
            'offset' => max($offset - $size, 0),
            'limit' => $size,
          ],
        ];
        break;
    }
    return array_merge($query, $extra_query);
  }

}
