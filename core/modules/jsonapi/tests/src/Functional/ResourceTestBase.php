<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Random;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityNullStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\Core\TypedData\TypedDataInternalPropertiesHelper;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\NullIncludedData;
use Drupal\jsonapi\JsonApiResource\Link;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\Normalizer\HttpExceptionNormalizer;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\jsonapi\ResourceResponse;
use Drupal\path\Plugin\Field\FieldType\PathItem;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\user\Entity\Role;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Subclass this for every JSON:API resource type.
 */
abstract class ResourceTestBase extends BrowserTestBase {

  use ResourceResponseTestTrait;
  use ContentModerationTestTrait;
  use JsonApiRequestTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'jsonapi',
    'basic_auth',
    'rest_test',
    'jsonapi_test_field_access',
    'text',
  ];

  /**
   * The tested entity type.
   *
   * @var string
   */
  protected static $entityTypeId = NULL;

  /**
   * The name of the tested JSON:API resource type.
   *
   * @var string
   */
  protected static $resourceTypeName = NULL;

  /**
   * Whether the tested JSON:API resource is versionable.
   *
   * @var bool
   */
  protected static $resourceTypeIsVersionable = FALSE;

  /**
   * The JSON:API resource type for the tested entity type plus bundle.
   *
   * Necessary for looking up public (alias) or internal (actual) field names.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceType
   */
  protected $resourceType;

  /**
   * The fields that are protected against modification during PATCH requests.
   *
   * @var string[]
   */
  protected static $patchProtectedFieldNames;

  /**
   * Fields that need unique values.
   *
   * @var string[]
   *
   * @see ::testPostIndividual()
   * @see ::getModifiedEntityForPostTesting()
   */
  protected static $uniqueFieldNames = [];

  /**
   * The entity ID for the first created entity in testPost().
   *
   * The default value of 2 should work for most content entities.
   *
   * @var string|int
   *
   * @see ::testPostIndividual()
   */
  protected static $firstCreatedEntityId = 2;

  /**
   * The entity ID for the second created entity in testPost().
   *
   * The default value of 3 should work for most content entities.
   *
   * @var string|int
   *
   * @see ::testPostIndividual()
   */
  protected static $secondCreatedEntityId = 3;

  /**
   * Optionally specify which field is the 'label' field.
   *
   * Some entities specify a 'label_callback', but not a 'label' entity key.
   * For example: User.
   *
   * @var string|null
   *
   * @see ::getInvalidNormalizedEntityToCreate()
   */
  protected static $labelFieldName = NULL;

  /**
   * Whether new revisions of updated entities should be created by default.
   *
   * @var bool
   */
  protected static $newRevisionsShouldBeAutomatic = FALSE;

  /**
   * Whether anonymous users can view labels of this resource type.
   *
   * @var bool
   */
  protected static $anonymousUsersCanViewLabels = FALSE;

  /**
   * The standard `jsonapi` top-level document member.
   *
   * @var array
   */
  protected static $jsonApiMember = [
    'version' => '1.0',
    'meta' => [
      'links' => ['self' => ['href' => 'http://jsonapi.org/format/1.0/']],
    ],
  ];

  /**
   * The entity being tested.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Another entity of the same type used for testing.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $anotherEntity;

  /**
   * The account to use for authentication.
   *
   * @var null|\Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * The UUID key.
   *
   * @var string
   */
  protected $uuidKey;

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->serializer = $this->container->get('jsonapi.serializer');

    // Ensure the anonymous user role has no permissions at all.
    $user_role = Role::load(RoleInterface::ANONYMOUS_ID);
    foreach ($user_role->getPermissions() as $permission) {
      $user_role->revokePermission($permission);
    }
    $user_role->save();
    assert([] === $user_role->getPermissions(), 'The anonymous user role has no permissions at all.');

    // Ensure the authenticated user role has no permissions at all.
    $user_role = Role::load(RoleInterface::AUTHENTICATED_ID);
    foreach ($user_role->getPermissions() as $permission) {
      $user_role->revokePermission($permission);
    }
    $user_role->save();
    assert([] === $user_role->getPermissions(), 'The authenticated user role has no permissions at all.');

    // Create an account, which tests will use. Also ensure the @current_user
    // service this account, to ensure certain access check logic in tests works
    // as expected.
    $this->account = $this->createUser();
    $this->container->get('current_user')->setAccount($this->account);

    // Create an entity.
    $entity_type_manager = $this->container->get('entity_type.manager');
    $this->entityStorage = $entity_type_manager->getStorage(static::$entityTypeId);
    $this->uuidKey = $entity_type_manager->getDefinition(static::$entityTypeId)
      ->getKey('uuid');
    $this->entity = $this->setUpFields($this->createEntity(), $this->account);

    $this->resourceType = $this->container->get('jsonapi.resource_type.repository')->getByTypeName(static::$resourceTypeName);
  }

  /**
   * Sets up additional fields for testing.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The primary test entity.
   * @param \Drupal\user\UserInterface $account
   *   The primary test user account.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The reloaded entity with the new fields attached.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setUpFields(EntityInterface $entity, UserInterface $account) {
    if (!$entity instanceof FieldableEntityInterface) {
      return $entity;
    }

    $entity_bundle = $entity->bundle();
    $account_bundle = $account->bundle();

    // Add access-protected field.
    FieldStorageConfig::create([
      'entity_type' => static::$entityTypeId,
      'field_name' => 'field_rest_test',
      'type' => 'text',
    ])
      ->setCardinality(1)
      ->save();
    FieldConfig::create([
      'entity_type' => static::$entityTypeId,
      'field_name' => 'field_rest_test',
      'bundle' => $entity_bundle,
    ])
      ->setLabel('Test field')
      ->setTranslatable(FALSE)
      ->save();

    FieldStorageConfig::create([
      'entity_type' => static::$entityTypeId,
      'field_name' => 'field_jsonapi_test_entity_ref',
      'type' => 'entity_reference',
    ])
      ->setSetting('target_type', 'user')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->save();

    FieldConfig::create([
      'entity_type' => static::$entityTypeId,
      'field_name' => 'field_jsonapi_test_entity_ref',
      'bundle' => $entity_bundle,
    ])
      ->setTranslatable(FALSE)
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', [
        'target_bundles' => NULL,
      ])
      ->save();

    // Add multi-value field.
    FieldStorageConfig::create([
      'entity_type' => static::$entityTypeId,
      'field_name' => 'field_rest_test_multivalue',
      'type' => 'string',
    ])
      ->setCardinality(3)
      ->save();
    FieldConfig::create([
      'entity_type' => static::$entityTypeId,
      'field_name' => 'field_rest_test_multivalue',
      'bundle' => $entity_bundle,
    ])
      ->setLabel('Test field: multi-value')
      ->setTranslatable(FALSE)
      ->save();

    \Drupal::service('router.builder')->rebuildIfNeeded();

    // Reload entity so that it has the new field.
    $reloaded_entity = $this->entityLoadUnchanged($entity->id());
    // Some entity types are not stored, hence they cannot be reloaded.
    if ($reloaded_entity !== NULL) {
      $entity = $reloaded_entity;

      // Set a default value on the fields.
      $entity->set('field_rest_test', ['value' => 'All the faith he had had had had no effect on the outcome of his life.']);
      $entity->set('field_jsonapi_test_entity_ref', ['user' => $account->id()]);
      $entity->set('field_rest_test_multivalue', [['value' => 'One'], ['value' => 'Two']]);
      $entity->save();
    }

    return $entity;
  }

  /**
   * Sets up a collection of entities of the same type for testing.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The collection of entities to test.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function getData() {
    if ($this->entityStorage->getQuery()->count()->execute() < 2) {
      $this->createAnotherEntity('two');
    }
    $query = $this->entityStorage->getQuery()->sort($this->entity->getEntityType()->getKey('id'));
    return $this->entityStorage->loadMultiple($query->execute());
  }

  /**
   * Generates a JSON:API normalization for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to generate a JSON:API normalization for.
   * @param \Drupal\Core\Url $url
   *   The URL to use as the "self" link.
   *
   * @return array
   *   The JSON:API normalization for the given entity.
   */
  protected function normalize(EntityInterface $entity, Url $url) {
    $self_link = new Link(new CacheableMetadata(), $url, ['self']);
    $resource_type = $this->container->get('jsonapi.resource_type.repository')->getByTypeName(static::$resourceTypeName);
    $doc = new JsonApiDocumentTopLevel(new ResourceObjectData([ResourceObject::createFromEntity($resource_type, $entity)], 1), new NullIncludedData(), new LinkCollection(['self' => $self_link]));
    return $this->serializer->normalize($doc, 'api_json', [
      'resource_type' => $resource_type,
      'account' => $this->account,
    ])->getNormalization();
  }

  /**
   * Creates the entity to be tested.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity to be tested.
   */
  abstract protected function createEntity();

  /**
   * Creates another entity to be tested.
   *
   * @param mixed $key
   *   A unique key to be used for the ID and/or label of the duplicated entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Another entity based on $this->entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createAnotherEntity($key) {
    $duplicate = $this->getEntityDuplicate($this->entity, $key);
    // Some entity types are not stored, hence they cannot be reloaded.
    if (get_class($this->entityStorage) !== ContentEntityNullStorage::class) {
      $duplicate->set('field_rest_test', 'Second collection entity');
    }
    $duplicate->save();
    return $duplicate;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityDuplicate(EntityInterface $original, $key) {
    $duplicate = $original->createDuplicate();
    if ($label_key = $original->getEntityType()->getKey('label')) {
      $duplicate->set($label_key, $original->label() . '_' . $key);
    }
    if ($duplicate instanceof ConfigEntityInterface && $id_key = $duplicate->getEntityType()->getKey('id')) {
      $id = $original->id();
      $id_key = $duplicate->getEntityType()->getKey('id');
      $duplicate->set($id_key, $id . '_' . $key);
    }
    return $duplicate;
  }

  /**
   * Returns the expected JSON:API document for the entity.
   *
   * @see ::createEntity()
   *
   * @return array
   *   A JSON:API response document.
   */
  abstract protected function getExpectedDocument();

  /**
   * Returns the JSON:API POST document.
   *
   * @see ::testPostIndividual()
   *
   * @return array
   *   A JSON:API request document.
   */
  abstract protected function getPostDocument();

  /**
   * Returns the JSON:API PATCH document.
   *
   * By default, reuses ::getPostDocument(), which works fine for most entity
   * types. A counter example: the 'comment' entity type.
   *
   * @see ::testPatchIndividual()
   *
   * @return array
   *   A JSON:API request document.
   */
  protected function getPatchDocument() {
    return NestedArray::mergeDeep(['data' => ['id' => $this->entity->uuid()]], $this->getPostDocument());
  }

  /**
   * Returns the expected cacheability for an unauthorized response.
   *
   * @return \Drupal\Core\Cache\CacheableMetadata
   *   The expected cacheability.
   */
  protected function getExpectedUnauthorizedAccessCacheability() {
    return (new CacheableMetadata())
      ->setCacheTags(['4xx-response', 'http_response'])
      ->setCacheContexts(['url.site', 'user.permissions'])
      ->addCacheContexts($this->entity->getEntityType()->isRevisionable()
        ? ['url.query_args:resourceVersion']
        : []
      );
  }

  /**
   * The expected cache tags for the GET/HEAD response of the test entity.
   *
   * @param array|null $sparse_fieldset
   *   If a sparse fieldset is being requested, limit the expected cache tags
   *   for this entity's fields to just these fields.
   *
   * @return string[]
   *   A set of cache tags.
   *
   * @see ::testGetIndividual()
   */
  protected function getExpectedCacheTags(array $sparse_fieldset = NULL) {
    $expected_cache_tags = [
      'http_response',
    ];
    return Cache::mergeTags($expected_cache_tags, $this->entity->getCacheTags());
  }

  /**
   * The expected cache contexts for the GET/HEAD response of the test entity.
   *
   * @param array|null $sparse_fieldset
   *   If a sparse fieldset is being requested, limit the expected cache
   *   contexts for this entity's fields to just these fields.
   *
   * @return string[]
   *   A set of cache contexts.
   *
   * @see ::testGetIndividual()
   */
  protected function getExpectedCacheContexts(array $sparse_fieldset = NULL) {
    $cache_contexts = [
      // Cache contexts for JSON:API URL query parameters.
      'url.query_args:fields',
      'url.query_args:include',
      // Drupal defaults.
      'url.site',
      'user.permissions',
    ];
    $entity_type = $this->entity->getEntityType();
    return Cache::mergeContexts($cache_contexts, $entity_type->isRevisionable() ? ['url.query_args:resourceVersion'] : []);
  }

  /**
   * Computes the cacheability for a given entity collection.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   An account for which cacheability should be computed (cacheability is
   *   dependent on access).
   * @param \Drupal\Core\Entity\EntityInterface[] $collection
   *   The entities for which cacheability should be computed.
   * @param array $sparse_fieldset
   *   (optional) If a sparse fieldset is being requested, limit the expected
   *   cacheability for the collection entities' fields to just those in the
   *   fieldset. NULL means all fields.
   * @param bool $filtered
   *   Whether the collection is filtered or not.
   *
   * @return \Drupal\Core\Cache\CacheableMetadata
   *   The expected cacheability for the given entity collection.
   */
  protected static function getExpectedCollectionCacheability(AccountInterface $account, array $collection, array $sparse_fieldset = NULL, $filtered = FALSE) {
    $cacheability = array_reduce($collection, function (CacheableMetadata $cacheability, EntityInterface $entity) use ($sparse_fieldset, $account) {
      $access_result = static::entityAccess($entity, 'view', $account);
      if (!$access_result->isAllowed()) {
        $access_result = static::entityAccess($entity, 'view label', $account)->addCacheableDependency($access_result);
      }
      $cacheability->addCacheableDependency($access_result);
      if ($access_result->isAllowed()) {
        $cacheability->addCacheableDependency($entity);
        if ($entity instanceof FieldableEntityInterface) {
          foreach ($entity as $field_name => $field_item_list) {
            /* @var \Drupal\Core\Field\FieldItemListInterface $field_item_list */
            if (is_null($sparse_fieldset) || in_array($field_name, $sparse_fieldset)) {
              $field_access = static::entityFieldAccess($entity, $field_name, 'view', $account);
              $cacheability->addCacheableDependency($field_access);
              if ($field_access->isAllowed()) {
                foreach ($field_item_list as $field_item) {
                  /* @var \Drupal\Core\Field\FieldItemInterface $field_item */
                  foreach (TypedDataInternalPropertiesHelper::getNonInternalProperties($field_item) as $property) {
                    $cacheability->addCacheableDependency(CacheableMetadata::createFromObject($property));
                  }
                }
              }
            }
          }
        }
      }
      return $cacheability;
    }, new CacheableMetadata());
    $entity_type = reset($collection)->getEntityType();
    $cacheability->addCacheTags(['http_response']);
    $cacheability->addCacheTags($entity_type->getListCacheTags());
    $cache_contexts = [
      // Cache contexts for JSON:API URL query parameters.
      'url.query_args:fields',
      'url.query_args:filter',
      'url.query_args:include',
      'url.query_args:page',
      'url.query_args:sort',
      // Drupal defaults.
      'url.site',
    ];
    // If the entity type is revisionable, add a resource version cache context.
    $cache_contexts = Cache::mergeContexts($cache_contexts, $entity_type->isRevisionable() ? ['url.query_args:resourceVersion'] : []);
    $cacheability->addCacheContexts($cache_contexts);
    return $cacheability;
  }

  /**
   * Sets up the necessary authorization.
   *
   * In case of a test verifying publicly accessible REST resources: grant
   * permissions to the anonymous user role.
   *
   * In case of a test verifying behavior when using a particular authentication
   * provider: create a user with a particular set of permissions.
   *
   * Because of the $method parameter, it's possible to first set up
   * authentication for only GET, then add POST, et cetera. This then also
   * allows for verifying a 403 in case of missing authorization.
   *
   * @param string $method
   *   The HTTP method for which to set up authentication.
   *
   * @see ::grantPermissionsToAnonymousRole()
   * @see ::grantPermissionsToAuthenticatedRole()
   */
  abstract protected function setUpAuthorization($method);

  /**
   * Sets up the necessary authorization for handling revisions.
   *
   * @param string $method
   *   The HTTP method for which to set up authentication.
   *
   * @see ::testRevisions()
   */
  protected function setUpRevisionAuthorization($method) {
    assert($method === 'GET', 'Only read operations on revisions are supported.');
    $this->setUpAuthorization($method);
  }

  /**
   * Return the expected error message.
   *
   * @param string $method
   *   The HTTP method (GET, POST, PATCH, DELETE).
   *
   * @return string
   *   The error string.
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    $permission = $this->entity->getEntityType()->getAdminPermission();
    if ($permission !== FALSE) {
      return "The '{$permission}' permission is required.";
    }

    return NULL;
  }

  /**
   * Grants permissions to the authenticated role.
   *
   * @param string[] $permissions
   *   Permissions to grant.
   */
  protected function grantPermissionsToTestedRole(array $permissions) {
    $this->grantPermissions(Role::load(RoleInterface::AUTHENTICATED_ID), $permissions);
  }

  /**
   * Revokes permissions from the authenticated role.
   *
   * @param string[] $permissions
   *   Permissions to revoke.
   */
  protected function revokePermissionsFromTestedRole(array $permissions) {
    $role = Role::load(RoleInterface::AUTHENTICATED_ID);
    foreach ($permissions as $permission) {
      $role->revokePermission($permission);
    }
    $role->trustData()->save();
  }

  /**
   * Asserts that a resource response has the given status code and body.
   *
   * @param int $expected_status_code
   *   The expected response status.
   * @param array|null|false $expected_document
   *   The expected document or NULL if there should not be a response body.
   *   FALSE in case this should not be asserted.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response to assert.
   * @param string[]|false $expected_cache_tags
   *   (optional) The expected cache tags in the X-Drupal-Cache-Tags response
   *   header, or FALSE if that header should be absent. Defaults to FALSE.
   * @param string[]|false $expected_cache_contexts
   *   (optional) The expected cache contexts in the X-Drupal-Cache-Contexts
   *   response header, or FALSE if that header should be absent. Defaults to
   *   FALSE.
   * @param string|false $expected_page_cache_header_value
   *   (optional) The expected X-Drupal-Cache response header value, or FALSE if
   *   that header should be absent. Possible strings: 'MISS', 'HIT'. Defaults
   *   to FALSE.
   * @param string|false $expected_dynamic_page_cache_header_value
   *   (optional) The expected X-Drupal-Dynamic-Cache response header value, or
   *   FALSE if that header should be absent. Possible strings: 'MISS', 'HIT'.
   *   Defaults to FALSE.
   */
  protected function assertResourceResponse($expected_status_code, $expected_document, ResponseInterface $response, $expected_cache_tags = FALSE, $expected_cache_contexts = FALSE, $expected_page_cache_header_value = FALSE, $expected_dynamic_page_cache_header_value = FALSE) {
    $this->assertSame($expected_status_code, $response->getStatusCode(), var_export(Json::decode((string) $response->getBody()), TRUE));
    if ($expected_status_code === 204) {
      // DELETE responses should not include a Content-Type header. But Apache
      // sets it to 'text/html' by default. We also cannot detect the presence
      // of Apache either here in the CLI. For now having this documented here
      // is all we can do.
      /* $this->assertSame(FALSE, $response->hasHeader('Content-Type')); */
      $this->assertSame('', (string) $response->getBody());
    }
    else {
      $this->assertSame(['application/vnd.api+json'], $response->getHeader('Content-Type'));
      if ($expected_document !== FALSE) {
        $response_document = Json::decode((string) $response->getBody());
        if ($expected_document === NULL) {
          $this->assertNull($response_document);
        }
        else {
          $this->assertSameDocument($expected_document, $response_document);
        }
      }
    }

    // Expected cache tags: X-Drupal-Cache-Tags header.
    $this->assertSame($expected_cache_tags !== FALSE, $response->hasHeader('X-Drupal-Cache-Tags'));
    if (is_array($expected_cache_tags)) {
      $this->assertSame($expected_cache_tags, explode(' ', $response->getHeader('X-Drupal-Cache-Tags')[0]));
    }

    // Expected cache contexts: X-Drupal-Cache-Contexts header.
    $this->assertSame($expected_cache_contexts !== FALSE, $response->hasHeader('X-Drupal-Cache-Contexts'));
    if (is_array($expected_cache_contexts)) {
      $optimized_expected_cache_contexts = \Drupal::service('cache_contexts_manager')->optimizeTokens($expected_cache_contexts);
      $this->assertSame($optimized_expected_cache_contexts, explode(' ', $response->getHeader('X-Drupal-Cache-Contexts')[0]));
    }

    // Expected Page Cache header value: X-Drupal-Cache header.
    if ($expected_page_cache_header_value !== FALSE) {
      $this->assertTrue($response->hasHeader('X-Drupal-Cache'));
      $this->assertSame($expected_page_cache_header_value, $response->getHeader('X-Drupal-Cache')[0]);
    }
    else {
      $this->assertFalse($response->hasHeader('X-Drupal-Cache'));
    }

    // Expected Dynamic Page Cache header value: X-Drupal-Dynamic-Cache header.
    if ($expected_dynamic_page_cache_header_value !== FALSE) {
      $this->assertTrue($response->hasHeader('X-Drupal-Dynamic-Cache'));
      $this->assertSame($expected_dynamic_page_cache_header_value, $response->getHeader('X-Drupal-Dynamic-Cache')[0]);
    }
    else {
      $this->assertFalse($response->hasHeader('X-Drupal-Dynamic-Cache'));
    }
  }

  /**
   * Asserts that an expected document matches the response body.
   *
   * @param array $expected_document
   *   The expected JSON:API document.
   * @param array $actual_document
   *   The actual response document to assert.
   */
  protected function assertSameDocument(array $expected_document, array $actual_document) {
    static::recursiveKsort($expected_document);
    static::recursiveKsort($actual_document);

    if (!empty($expected_document['included'])) {
      static::sortResourceCollection($expected_document['included']);
      static::sortResourceCollection($actual_document['included']);
    }

    if (isset($actual_document['meta']['omitted']) && isset($expected_document['meta']['omitted'])) {
      $actual_omitted =& $actual_document['meta']['omitted'];
      $expected_omitted =& $expected_document['meta']['omitted'];
      static::sortOmittedLinks($actual_omitted);
      static::sortOmittedLinks($expected_omitted);
      static::resetOmittedLinkKeys($actual_omitted);
      static::resetOmittedLinkKeys($expected_omitted);
    }

    $expected_keys = array_keys($expected_document);
    $actual_keys = array_keys($actual_document);
    $missing_member_names = array_diff($expected_keys, $actual_keys);
    $extra_member_names = array_diff($actual_keys, $expected_keys);
    if (!empty($missing_member_names) || !empty($extra_member_names)) {
      $message_format = "The document members did not match the expected values. Missing: [ %s ]. Unexpected: [ %s ]";
      $message = sprintf($message_format, implode(', ', $missing_member_names), implode(', ', $extra_member_names));
      $this->assertSame($expected_document, $actual_document, $message);
    }
    foreach ($expected_document as $member_name => $expected_member) {
      $actual_member = $actual_document[$member_name];
      $this->assertSame($expected_member, $actual_member, "The '$member_name' member was not as expected.");
    }
  }

  /**
   * Asserts that a resource error response has the given message.
   *
   * @param int $expected_status_code
   *   The expected response status.
   * @param string $expected_message
   *   The expected error message.
   * @param \Drupal\Core\Url|null $via_link
   *   The source URL for the errors of the response. NULL if the error occurs
   *   for example during entity creation.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The error response to assert.
   * @param string|false $pointer
   *   The expected JSON Pointer to the associated entity in the request
   *   document. See http://jsonapi.org/format/#error-objects.
   * @param string[]|false $expected_cache_tags
   *   (optional) The expected cache tags in the X-Drupal-Cache-Tags response
   *   header, or FALSE if that header should be absent. Defaults to FALSE.
   * @param string[]|false $expected_cache_contexts
   *   (optional) The expected cache contexts in the X-Drupal-Cache-Contexts
   *   response header, or FALSE if that header should be absent. Defaults to
   *   FALSE.
   * @param string|false $expected_page_cache_header_value
   *   (optional) The expected X-Drupal-Cache response header value, or FALSE if
   *   that header should be absent. Possible strings: 'MISS', 'HIT'. Defaults
   *   to FALSE.
   * @param string|false $expected_dynamic_page_cache_header_value
   *   (optional) The expected X-Drupal-Dynamic-Cache response header value, or
   *   FALSE if that header should be absent. Possible strings: 'MISS', 'HIT'.
   *   Defaults to FALSE.
   */
  protected function assertResourceErrorResponse($expected_status_code, $expected_message, $via_link, ResponseInterface $response, $pointer = FALSE, $expected_cache_tags = FALSE, $expected_cache_contexts = FALSE, $expected_page_cache_header_value = FALSE, $expected_dynamic_page_cache_header_value = FALSE) {
    assert(is_null($via_link) || $via_link instanceof Url);
    $expected_error = [];
    if (!empty(Response::$statusTexts[$expected_status_code])) {
      $expected_error['title'] = Response::$statusTexts[$expected_status_code];
    }
    $expected_error['status'] = (string) $expected_status_code;
    $expected_error['detail'] = $expected_message;
    if ($via_link) {
      $expected_error['links']['via']['href'] = $via_link->setAbsolute()->toString();
    }
    if ($info_url = HttpExceptionNormalizer::getInfoUrl($expected_status_code)) {
      $expected_error['links']['info']['href'] = $info_url;
    }
    if ($pointer !== FALSE) {
      $expected_error['source']['pointer'] = $pointer;
    }

    $expected_document = [
      'jsonapi' => static::$jsonApiMember,
      'errors' => [
        0 => $expected_error,
      ],
    ];
    $this->assertResourceResponse($expected_status_code, $expected_document, $response, $expected_cache_tags, $expected_cache_contexts, $expected_page_cache_header_value, $expected_dynamic_page_cache_header_value);
  }

  /**
   * Makes the JSON:API document violate the spec by omitting the resource type.
   *
   * @param array $document
   *   A JSON:API document.
   *
   * @return array
   *   The same JSON:API document, without its resource type.
   */
  protected function removeResourceTypeFromDocument(array $document) {
    unset($document['data']['type']);
    return $document;
  }

  /**
   * Makes the given JSON:API document invalid.
   *
   * @param array $document
   *   A JSON:API document.
   * @param string $entity_key
   *   The entity key whose normalization to make invalid.
   *
   * @return array
   *   The updated JSON:API document, now invalid.
   */
  protected function makeNormalizationInvalid(array $document, $entity_key) {
    $entity_type = $this->entity->getEntityType();
    switch ($entity_key) {
      case 'label':
        // Add a second label to this entity to make it invalid.
        $label_field = $entity_type->hasKey('label') ? $entity_type->getKey('label') : static::$labelFieldName;
        $document['data']['attributes'][$label_field] = [
          0 => $document['data']['attributes'][$label_field],
          1 => 'Second Title',
        ];
        break;

      case 'id':
        $document['data']['attributes'][$entity_type->getKey('id')] = $this->anotherEntity->id();
        break;

      case 'uuid':
        $document['data']['id'] = $this->anotherEntity->uuid();
        break;
    }

    return $document;
  }

  /**
   * Tests GETting an individual resource, plus edge cases to ensure good DX.
   */
  public function testGetIndividual() {
    // The URL and Guzzle request options that will be used in this test. The
    // request options will be modified/expanded throughout this test:
    // - to first test all mistakes a developer might make, and assert that the
    //   error responses provide a good DX
    // - to eventually result in a well-formed request that succeeds.
    // @todo Remove line below in favor of commented line in https://www.drupal.org/project/jsonapi/issues/2878463.
    $url = Url::fromRoute(sprintf('jsonapi.%s.individual', static::$resourceTypeName), ['entity' => $this->entity->uuid()]);
    /* $url = $this->entity->toUrl('jsonapi'); */
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    // DX: 403 when unauthorized, or 200 if the 'view label' operation is
    // supported by the entity type.
    $response = $this->request('GET', $url, $request_options);
    if (!static::$anonymousUsersCanViewLabels) {
      $expected_403_cacheability = $this->getExpectedUnauthorizedAccessCacheability();
      $reason = $this->getExpectedUnauthorizedAccessMessage('GET');
      $message = trim("The current user is not allowed to GET the selected resource. $reason");
      $this->assertResourceErrorResponse(403, $message, $url, $response, '/data', $expected_403_cacheability->getCacheTags(), $expected_403_cacheability->getCacheContexts(), FALSE, 'MISS');
      $this->assertArrayNotHasKey('Link', $response->getHeaders());
    }
    else {
      $expected_document = $this->getExpectedDocument();
      $label_field_name = $this->entity->getEntityType()->hasKey('label') ? $this->entity->getEntityType()->getKey('label') : static::$labelFieldName;
      $expected_document['data']['attributes'] = array_intersect_key($expected_document['data']['attributes'], [$label_field_name => TRUE]);
      unset($expected_document['data']['relationships']);
      // MISS or UNCACHEABLE depends on data. It must not be HIT.
      $dynamic_cache_label_only = !empty(array_intersect(['user', 'session'], $this->getExpectedCacheContexts([$label_field_name]))) ? 'UNCACHEABLE' : 'MISS';
      $this->assertResourceResponse(200, $expected_document, $response, $this->getExpectedCacheTags(), $this->getExpectedCacheContexts([$label_field_name]), FALSE, $dynamic_cache_label_only);
    }

    $this->setUpAuthorization('GET');

    // Set body despite that being nonsensical: should be ignored.
    $request_options[RequestOptions::BODY] = Json::encode($this->getExpectedDocument());

    // 400 for GET request with reserved custom query parameter.
    $url_reserved_custom_query_parameter = clone $url;
    $url_reserved_custom_query_parameter = $url_reserved_custom_query_parameter->setOption('query', ['foo' => 'bar']);
    $response = $this->request('GET', $url_reserved_custom_query_parameter, $request_options);
    $expected_document = [
      'jsonapi' => static::$jsonApiMember,
      'errors' => [
        [
          'title' => 'Bad Request',
          'status' => '400',
          'detail' => "The following query parameters violate the JSON:API spec: 'foo'.",
          'links' => [
            'info' => ['href' => 'http://jsonapi.org/format/#query-parameters'],
            'via' => ['href' => $url_reserved_custom_query_parameter->toString()],
          ],
        ],
      ],
    ];
    $this->assertResourceResponse(400, $expected_document, $response, ['4xx-response', 'http_response'], ['url.query_args', 'url.site'], FALSE, 'MISS');

    // 200 for well-formed HEAD request.
    $response = $this->request('HEAD', $url, $request_options);
    // MISS or UNCACHEABLE depends on data. It must not be HIT.
    $dynamic_cache = !empty(array_intersect(['user', 'session'], $this->getExpectedCacheContexts())) ? 'UNCACHEABLE' : 'MISS';
    $this->assertResourceResponse(200, NULL, $response, $this->getExpectedCacheTags(), $this->getExpectedCacheContexts(), FALSE, $dynamic_cache);
    $head_headers = $response->getHeaders();

    // 200 for well-formed GET request. Page Cache hit because of HEAD request.
    // Same for Dynamic Page Cache hit.
    $response = $this->request('GET', $url, $request_options);

    $this->assertResourceResponse(200, $this->getExpectedDocument(), $response, $this->getExpectedCacheTags(), $this->getExpectedCacheContexts(), FALSE, $dynamic_cache === 'MISS' ? 'HIT' : 'UNCACHEABLE');
    // Assert that Dynamic Page Cache did not store a ResourceResponse object,
    // which needs serialization after every cache hit. Instead, it should
    // contain a flattened response. Otherwise performance suffers.
    // @see \Drupal\jsonapi\EventSubscriber\ResourceResponseSubscriber::flattenResponse()
    $cache_items = $this->container->get('database')
      ->query("SELECT cid, data FROM {cache_dynamic_page_cache} WHERE cid LIKE :pattern", [
        ':pattern' => '%[route]=jsonapi.%',
      ])
      ->fetchAllAssoc('cid');
    $this->assertTrue(count($cache_items) >= 2);
    $found_cache_redirect = FALSE;
    $found_cached_200_response = FALSE;
    $other_cached_responses_are_4xx = TRUE;
    foreach ($cache_items as $cid => $cache_item) {
      $cached_data = unserialize($cache_item->data);
      if (!isset($cached_data['#cache_redirect'])) {
        $cached_response = $cached_data['#response'];
        if ($cached_response->getStatusCode() === 200) {
          $found_cached_200_response = TRUE;
        }
        elseif (!$cached_response->isClientError()) {
          $other_cached_responses_are_4xx = FALSE;
        }
        $this->assertNotInstanceOf(ResourceResponse::class, $cached_response);
        $this->assertInstanceOf(CacheableResponseInterface::class, $cached_response);
      }
      else {
        $found_cache_redirect = TRUE;
      }
    }
    $this->assertTrue($found_cache_redirect);
    $this->assertSame($dynamic_cache !== 'UNCACHEABLE' || isset($dynamic_cache_label_only) && $dynamic_cache_label_only !== 'UNCACHEABLE', $found_cached_200_response);
    $this->assertTrue($other_cached_responses_are_4xx);

    // Not only assert the normalization, also assert deserialization of the
    // response results in the expected object.
    $unserialized = $this->serializer->deserialize((string) $response->getBody(), JsonApiDocumentTopLevel::class, 'api_json', [
      'target_entity' => static::$entityTypeId,
      'resource_type' => $this->container->get('jsonapi.resource_type.repository')->getByTypeName(static::$resourceTypeName),
    ]);
    $this->assertSame($unserialized->uuid(), $this->entity->uuid());
    $get_headers = $response->getHeaders();

    // Verify that the GET and HEAD responses are the same. The only difference
    // is that there's no body. For this reason the 'Transfer-Encoding' and
    // 'Vary' headers are also added to the list of headers to ignore, as they
    // may be added to GET requests, depending on web server configuration. They
    // are usually 'Transfer-Encoding: chunked' and 'Vary: Accept-Encoding'.
    $ignored_headers = [
      'Date',
      'Content-Length',
      'X-Drupal-Cache',
      'X-Drupal-Dynamic-Cache',
      'Transfer-Encoding',
      'Vary',
    ];
    $header_cleaner = function ($headers) use ($ignored_headers) {
      foreach ($headers as $header => $value) {
        if (strpos($header, 'X-Drupal-Assertion-') === 0 || in_array($header, $ignored_headers)) {
          unset($headers[$header]);
        }
      }
      return $headers;
    };
    $get_headers = $header_cleaner($get_headers);
    $head_headers = $header_cleaner($head_headers);
    $this->assertSame($get_headers, $head_headers);

    // Feature: Sparse fieldsets.
    $this->doTestSparseFieldSets($url, $request_options);
    // Feature: Included.
    $this->doTestIncluded($url, $request_options);

    // DX: 404 when GETting non-existing entity.
    $random_uuid = \Drupal::service('uuid')->generate();
    $url = Url::fromRoute(sprintf('jsonapi.%s.individual', static::$resourceTypeName), ['entity' => $random_uuid]);
    $response = $this->request('GET', $url, $request_options);
    $message_url = clone $url;
    $path = str_replace($random_uuid, '{entity}', $message_url->setAbsolute()->setOptions(['base_url' => '', 'query' => []])->toString());
    $message = 'The "entity" parameter was not converted for the path "' . $path . '" (route name: "jsonapi.' . static::$resourceTypeName . '.individual")';
    $this->assertResourceErrorResponse(404, $message, $url, $response, FALSE, ['4xx-response', 'http_response'], ['url.site'], FALSE, 'UNCACHEABLE');

    // DX: when Accept request header is missing, still 404, same response.
    unset($request_options[RequestOptions::HEADERS]['Accept']);
    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceErrorResponse(404, $message, $url, $response, FALSE, ['4xx-response', 'http_response'], ['url.site'], FALSE, 'UNCACHEABLE');
  }

  /**
   * Tests GETting a collection of resources.
   */
  public function testCollection() {
    $entity_collection = $this->getData();
    assert(count($entity_collection) > 1, 'A collection must have more that one entity in it.');

    $collection_url = Url::fromRoute(sprintf('jsonapi.%s.collection', static::$resourceTypeName))->setAbsolute(TRUE);
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    // This asserts that collections will work without a sort, added by default
    // below, without actually asserting the content of the response.
    $expected_response = $this->getExpectedCollectionResponse($entity_collection, $collection_url->toString(), $request_options);
    $expected_cacheability = $expected_response->getCacheableMetadata();
    $response = $this->request('HEAD', $collection_url, $request_options);
    // MISS or UNCACHEABLE depends on the collection data. It must not be HIT.
    $dynamic_cache = $expected_cacheability->getCacheMaxAge() === 0 ? 'UNCACHEABLE' : 'MISS';
    $this->assertResourceResponse(200, NULL, $response, $expected_cacheability->getCacheTags(), $expected_cacheability->getCacheContexts(), FALSE, $dynamic_cache);

    // Different databases have different sort orders, so a sort is required so
    // test expectations do not need to vary per database.
    $default_sort = ['sort' => 'drupal_internal__' . $this->entity->getEntityType()->getKey('id')];
    $collection_url->setOption('query', $default_sort);

    // 200 for collections, even when all entities are inaccessible. Access is
    // on a per-entity basis, which is handled by
    // self::getExpectedCollectionResponse().
    $expected_response = $this->getExpectedCollectionResponse($entity_collection, $collection_url->toString(), $request_options);
    $expected_cacheability = $expected_response->getCacheableMetadata();
    $expected_document = $expected_response->getResponseData();
    $response = $this->request('GET', $collection_url, $request_options);
    $this->assertResourceResponse(200, $expected_document, $response, $expected_cacheability->getCacheTags(), $expected_cacheability->getCacheContexts(), FALSE, $dynamic_cache);

    $this->setUpAuthorization('GET');

    // 200 for well-formed HEAD request.
    $expected_response = $this->getExpectedCollectionResponse($entity_collection, $collection_url->toString(), $request_options);
    $expected_cacheability = $expected_response->getCacheableMetadata();
    $response = $this->request('HEAD', $collection_url, $request_options);
    $this->assertResourceResponse(200, NULL, $response, $expected_cacheability->getCacheTags(), $expected_cacheability->getCacheContexts(), FALSE, $dynamic_cache);

    // 200 for well-formed GET request.
    $expected_response = $this->getExpectedCollectionResponse($entity_collection, $collection_url->toString(), $request_options);
    $expected_cacheability = $expected_response->getCacheableMetadata();
    $expected_document = $expected_response->getResponseData();
    $response = $this->request('GET', $collection_url, $request_options);
    // Dynamic Page Cache HIT unless the HEAD request was UNCACHEABLE.
    $dynamic_cache = $dynamic_cache === 'UNCACHEABLE' ? 'UNCACHEABLE' : 'HIT';
    $this->assertResourceResponse(200, $expected_document, $response, $expected_cacheability->getCacheTags(), $expected_cacheability->getCacheContexts(), FALSE, $dynamic_cache);

    if ($this->entity instanceof FieldableEntityInterface) {
      // 403 for filtering on an unauthorized field on the base resource type.
      $unauthorized_filter_url = clone $collection_url;
      $unauthorized_filter_url->setOption('query', [
        'filter' => [
          'related_author_id' => [
            'operator' => '<>',
            'path' => 'field_jsonapi_test_entity_ref.status',
            'value' => 'doesnt@matter.com',
          ],
        ],
      ]);
      $response = $this->request('GET', $unauthorized_filter_url, $request_options);
      $expected_error_message = "The current user is not authorized to filter by the `field_jsonapi_test_entity_ref` field, given in the path `field_jsonapi_test_entity_ref`. The 'field_jsonapi_test_entity_ref view access' permission is required.";
      $expected_cache_tags = ['4xx-response', 'http_response'];
      $expected_cache_contexts = [
        'url.query_args:filter',
        'url.query_args:sort',
        'url.site',
        'user.permissions',
      ];
      $this->assertResourceErrorResponse(403, $expected_error_message, $unauthorized_filter_url, $response, FALSE, $expected_cache_tags, $expected_cache_contexts, FALSE, 'MISS');

      $this->grantPermissionsToTestedRole(['field_jsonapi_test_entity_ref view access']);

      // 403 for filtering on an unauthorized field on a related resource type.
      $response = $this->request('GET', $unauthorized_filter_url, $request_options);
      $expected_error_message = "The current user is not authorized to filter by the `status` field, given in the path `field_jsonapi_test_entity_ref.entity:user.status`.";
      $this->assertResourceErrorResponse(403, $expected_error_message, $unauthorized_filter_url, $response, FALSE, $expected_cache_tags, $expected_cache_contexts, FALSE, 'MISS');
    }

    // Remove an entity from the collection, then filter it out.
    $filtered_entity_collection = $entity_collection;
    $removed = array_shift($filtered_entity_collection);
    $filtered_collection_url = clone $collection_url;
    $entity_collection_filter = [
      'filter' => [
        'ids' => [
          'condition' => [
            'operator' => '<>',
            'path' => 'id',
            'value' => $removed->uuid(),
          ],
        ],
      ],
    ];
    $filtered_collection_url->setOption('query', $entity_collection_filter + $default_sort);
    $expected_response = $this->getExpectedCollectionResponse($filtered_entity_collection, $filtered_collection_url->toString(), $request_options, NULL, TRUE);
    $expected_cacheability = $expected_response->getCacheableMetadata();
    $expected_document = $expected_response->getResponseData();
    $response = $this->request('GET', $filtered_collection_url, $request_options);
    // MISS or UNCACHEABLE depends on the collection data. It must not be HIT.
    $dynamic_cache = $expected_cacheability->getCacheMaxAge() === 0 || !empty(array_intersect(['user', 'session'], $expected_cacheability->getCacheContexts())) ? 'UNCACHEABLE' : 'MISS';
    $this->assertResourceResponse(200, $expected_document, $response, $expected_cacheability->getCacheTags(), $expected_cacheability->getCacheContexts(), FALSE, $dynamic_cache);

    // Filtered collection with includes.
    $relationship_field_names = array_reduce($filtered_entity_collection, function ($relationship_field_names, $entity) {
      return array_unique(array_merge($relationship_field_names, $this->getRelationshipFieldNames($entity)));
    }, []);
    $include = ['include' => implode(',', $relationship_field_names)];
    $filtered_collection_include_url = clone $collection_url;
    $filtered_collection_include_url->setOption('query', $entity_collection_filter + $include + $default_sort);
    $expected_response = $this->getExpectedCollectionResponse($filtered_entity_collection, $filtered_collection_include_url->toString(), $request_options, $relationship_field_names, TRUE);
    $expected_cacheability = $expected_response->getCacheableMetadata();
    $expected_cacheability->setCacheTags(array_values(array_diff($expected_cacheability->getCacheTags(), ['4xx-response'])));
    $expected_document = $expected_response->getResponseData();
    $response = $this->request('GET', $filtered_collection_include_url, $request_options);
    // MISS or UNCACHEABLE depends on the included data. It must not be HIT.
    $dynamic_cache = $expected_cacheability->getCacheMaxAge() === 0 || !empty(array_intersect(['user', 'session'], $expected_cacheability->getCacheContexts())) ? 'UNCACHEABLE' : 'MISS';
    $this->assertResourceResponse(200, $expected_document, $response, $expected_cacheability->getCacheTags(), $expected_cacheability->getCacheContexts(), FALSE, $dynamic_cache);

    // If the response should vary by a user's authorizations, grant permissions
    // for the included resources and execute another request.
    $permission_related_cache_contexts = [
      'user',
      'user.permissions',
      'user.roles',
    ];
    if (!empty($relationship_field_names) && !empty(array_intersect($expected_cacheability->getCacheContexts(), $permission_related_cache_contexts))) {
      $applicable_permissions = array_intersect_key(static::getIncludePermissions(), array_flip($relationship_field_names));
      $flattened_permissions = array_unique(array_reduce($applicable_permissions, 'array_merge', []));
      $this->grantPermissionsToTestedRole($flattened_permissions);
      $expected_response = $this->getExpectedCollectionResponse($filtered_entity_collection, $filtered_collection_include_url->toString(), $request_options, $relationship_field_names, TRUE);
      $expected_cacheability = $expected_response->getCacheableMetadata();
      $expected_document = $expected_response->getResponseData();
      $response = $this->request('GET', $filtered_collection_include_url, $request_options);
      $requires_include_only_permissions = !empty($flattened_permissions);
      $uncacheable = $expected_cacheability->getCacheMaxAge() === 0 || !empty(array_intersect(['user', 'session'], $expected_cacheability->getCacheContexts()));
      $dynamic_cache = !$uncacheable ? $requires_include_only_permissions ? 'MISS' : 'HIT' : 'UNCACHEABLE';
      $this->assertResourceResponse(200, $expected_document, $response, $expected_cacheability->getCacheTags(), $expected_cacheability->getCacheContexts(), FALSE, $dynamic_cache);
    }

    // Sorted collection with includes.
    $sorted_entity_collection = $entity_collection;
    uasort($sorted_entity_collection, function (EntityInterface $a, EntityInterface $b) {
      // Sort by ID in reverse order.
      return strcmp($b->uuid(), $a->uuid());
    });
    $sorted_collection_include_url = clone $collection_url;
    $sorted_collection_include_url->setOption('query', $include + ['sort' => "-id"]);
    $expected_response = $this->getExpectedCollectionResponse($sorted_entity_collection, $sorted_collection_include_url->toString(), $request_options, $relationship_field_names);
    $expected_cacheability = $expected_response->getCacheableMetadata();
    $expected_cacheability->setCacheTags(array_values(array_diff($expected_cacheability->getCacheTags(), ['4xx-response'])));
    $expected_document = $expected_response->getResponseData();
    $response = $this->request('GET', $sorted_collection_include_url, $request_options);
    // MISS or UNCACHEABLE depends on the included data. It must not be HIT.
    $dynamic_cache = $expected_cacheability->getCacheMaxAge() === 0 ? 'UNCACHEABLE' : 'MISS';
    $this->assertResourceResponse(200, $expected_document, $response, $expected_cacheability->getCacheTags(), $expected_cacheability->getCacheContexts(), FALSE, $dynamic_cache);
  }

  /**
   * Returns a JSON:API collection document for the expected entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $collection
   *   The entities for the collection.
   * @param string $self_link
   *   The self link for the collection response document.
   * @param array $request_options
   *   Request options to apply.
   * @param array|null $included_paths
   *   (optional) Any include paths that should be appended to the expected
   *   response.
   * @param bool $filtered
   *   Whether the collection is filtered or not.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   A ResourceResponse for the expected entity collection.
   *
   * @see \GuzzleHttp\ClientInterface::request()
   */
  protected function getExpectedCollectionResponse(array $collection, $self_link, array $request_options, array $included_paths = NULL, $filtered = FALSE) {
    $resource_identifiers = array_map([static::class, 'toResourceIdentifier'], $collection);
    $individual_responses = static::toResourceResponses($this->getResponses(static::getResourceLinks($resource_identifiers), $request_options));
    $merged_response = static::toCollectionResourceResponse($individual_responses, $self_link, TRUE);

    $merged_document = $merged_response->getResponseData();
    if (!isset($merged_document['data'])) {
      $merged_document['data'] = [];
    }

    $cacheability = static::getExpectedCollectionCacheability($this->account, $collection, NULL, $filtered);
    $cacheability->setCacheMaxAge($merged_response->getCacheableMetadata()->getCacheMaxAge());

    $collection_response = ResourceResponse::create($merged_document);
    $collection_response->addCacheableDependency($cacheability);

    if (is_null($included_paths)) {
      return $collection_response;
    }

    $related_responses = array_reduce($collection, function ($related_responses, EntityInterface $entity) use ($included_paths, $request_options, $self_link) {
      if (!$entity->access('view', $this->account) && !$entity->access('view label', $this->account)) {
        return $related_responses;
      }
      $expected_related_responses = $this->getExpectedRelatedResponses($included_paths, $request_options, $entity);
      if (empty($related_responses)) {
        return $expected_related_responses;
      }
      foreach ($included_paths as $included_path) {
        $both_responses = [$related_responses[$included_path], $expected_related_responses[$included_path]];
        $related_responses[$included_path] = static::toCollectionResourceResponse($both_responses, $self_link, TRUE);
      }
      return $related_responses;
    }, []);

    return static::decorateExpectedResponseForIncludedFields($collection_response, $related_responses);
  }

  /**
   * Tests GETing related resource of an individual resource.
   *
   * Expected responses are built by making requests to 'relationship' routes.
   * Using the fetched resource identifiers, if any, all targeted resources are
   * fetched individually. These individual responses are then 'merged' into a
   * single expected ResourceResponse. This is repeated for every relationship
   * field of the resource type under test.
   */
  public function testRelated() {
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());
    $this->doTestRelated($request_options);
    $this->setUpAuthorization('GET');
    $this->doTestRelated($request_options);
  }

  /**
   * Tests CRUD of individual resource relationship data.
   *
   * Unlike the "related" routes, relationship routes only return information
   * about the "relationship" itself, not the targeted resources. For JSON:API
   * with Drupal, relationship routes are like looking at an entity reference
   * field without loading the entities. It only reveals the type of the
   * targeted resource and the target resource IDs. These type+ID combos are
   * referred to as "resource identifiers."
   */
  public function testRelationships() {
    if ($this->entity instanceof ConfigEntityInterface) {
      $this->markTestSkipped('Configuration entities cannot have relationships.');
    }

    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    // Test GET.
    $this->doTestRelationshipGet($request_options);
    $this->setUpAuthorization('GET');
    $this->doTestRelationshipGet($request_options);

    // Test POST.
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);
    $this->doTestRelationshipMutation($request_options);
    // Grant entity-level edit access.
    $this->setUpAuthorization('PATCH');
    $this->doTestRelationshipMutation($request_options);
    // Field edit access is still forbidden, grant it.
    $this->grantPermissionsToTestedRole([
      'field_jsonapi_test_entity_ref view access',
      'field_jsonapi_test_entity_ref edit access',
      'field_jsonapi_test_entity_ref update access',
    ]);
    $this->doTestRelationshipMutation($request_options);
  }

  /**
   * Performs one round of related route testing.
   *
   * By putting this behavior in its own method, authorization and other
   * variations can be done in the calling method around assertions. For
   * example, it can be run once with an authorized user and again without one.
   *
   * @param array $request_options
   *   Request options to apply.
   *
   * @see \GuzzleHttp\ClientInterface::request()
   */
  protected function doTestRelated(array $request_options) {
    $relationship_field_names = $this->getRelationshipFieldNames($this->entity);
    // If there are no relationship fields, we can't test related routes.
    if (empty($relationship_field_names)) {
      return;
    }
    // Builds an array of expected responses, keyed by relationship field name.
    $expected_relationship_responses = $this->getExpectedRelatedResponses($relationship_field_names, $request_options);
    // Fetches actual responses as an array keyed by relationship field name.
    $related_responses = $this->getRelatedResponses($relationship_field_names, $request_options);
    foreach ($relationship_field_names as $relationship_field_name) {
      /* @var \Drupal\jsonapi\ResourceResponse $expected_resource_response */
      $expected_resource_response = $expected_relationship_responses[$relationship_field_name];
      /* @var \Psr\Http\Message\ResponseInterface $actual_response */
      $actual_response = $related_responses[$relationship_field_name];
      // Dynamic Page Cache miss because cache should vary based on the
      // 'include' query param.
      $expected_cacheability = $expected_resource_response->getCacheableMetadata();
      $this->assertResourceResponse(
        $expected_resource_response->getStatusCode(),
        $expected_resource_response->getResponseData(),
        $actual_response,
        $expected_cacheability->getCacheTags(),
        $expected_cacheability->getCacheContexts(),
        FALSE,
        $actual_response->getStatusCode() === 200
          ? ($expected_cacheability->getCacheMaxAge() === 0 ? 'UNCACHEABLE' : 'MISS')
          : FALSE
      );
    }
  }

  /**
   * Performs one round of relationship route testing.
   *
   * @param array $request_options
   *   Request options to apply.
   *
   * @see \GuzzleHttp\ClientInterface::request()
   * @see ::testRelationships
   */
  protected function doTestRelationshipGet(array $request_options) {
    $relationship_field_names = $this->getRelationshipFieldNames($this->entity);
    // If there are no relationship fields, we can't test relationship routes.
    if (empty($relationship_field_names)) {
      return;
    }

    // Test GET.
    $related_responses = $this->getRelationshipResponses($relationship_field_names, $request_options);
    foreach ($relationship_field_names as $relationship_field_name) {
      $expected_resource_response = $this->getExpectedGetRelationshipResponse($relationship_field_name);
      $expected_document = $expected_resource_response->getResponseData();
      $expected_cacheability = $expected_resource_response->getCacheableMetadata();
      $actual_response = $related_responses[$relationship_field_name];
      $this->assertResourceResponse(
        $expected_resource_response->getStatusCode(),
        $expected_document,
        $actual_response,
        $expected_cacheability->getCacheTags(),
        $expected_cacheability->getCacheContexts(),
        FALSE,
        $expected_resource_response->isSuccessful() ? 'MISS' : FALSE
      );
    }
  }

  /**
   * Performs one round of relationship POST, PATCH and DELETE route testing.
   *
   * @param array $request_options
   *   Request options to apply.
   *
   * @see \GuzzleHttp\ClientInterface::request()
   * @see ::testRelationships
   */
  protected function doTestRelationshipMutation(array $request_options) {
    /* @var \Drupal\Core\Entity\FieldableEntityInterface $resource */
    $resource = $this->createAnotherEntity('dupe');
    $resource->set('field_jsonapi_test_entity_ref', NULL);
    $violations = $resource->validate();
    assert($violations->count() === 0, (string) $violations);
    $resource->save();
    $target_resource = $this->createUser();
    $violations = $target_resource->validate();
    assert($violations->count() === 0, (string) $violations);
    $target_resource->save();
    $target_identifier = static::toResourceIdentifier($target_resource);
    $resource_identifier = static::toResourceIdentifier($resource);
    $relationship_field_name = 'field_jsonapi_test_entity_ref';
    /* @var \Drupal\Core\Access\AccessResultReasonInterface $update_access */
    $update_access = static::entityAccess($resource, 'update', $this->account)
      ->andIf(static::entityFieldAccess($resource, $relationship_field_name, 'edit', $this->account));
    $url = Url::fromRoute(sprintf("jsonapi.{$resource_identifier['type']}.{$relationship_field_name}.relationship.patch"), [
      'entity' => $resource->uuid(),
    ]);

    // Test POST: missing content-type.
    $response = $this->request('POST', $url, $request_options);
    $this->assertSame(415, $response->getStatusCode());

    // Set the JSON:API media type header for all subsequent requests.
    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';

    if ($update_access->isAllowed()) {
      // Test POST: empty body.
      $response = $this->request('POST', $url, $request_options);
      $this->assertResourceErrorResponse(400, 'Empty request body.', $url, $response, FALSE);
      // Test PATCH: empty body.
      $response = $this->request('PATCH', $url, $request_options);
      $this->assertResourceErrorResponse(400, 'Empty request body.', $url, $response, FALSE);

      // Test POST: empty data.
      $request_options[RequestOptions::BODY] = Json::encode(['data' => []]);
      $response = $this->request('POST', $url, $request_options);
      $this->assertResourceResponse(204, NULL, $response);
      // Test PATCH: empty data.
      $request_options[RequestOptions::BODY] = Json::encode(['data' => []]);
      $response = $this->request('PATCH', $url, $request_options);
      $this->assertResourceResponse(204, NULL, $response);

      // Test POST: data as resource identifier, not array of identifiers.
      $request_options[RequestOptions::BODY] = Json::encode(['data' => $target_identifier]);
      $response = $this->request('POST', $url, $request_options);
      $this->assertResourceErrorResponse(400, 'Invalid body payload for the relationship.', $url, $response, FALSE);
      // Test PATCH: data as resource identifier, not array of identifiers.
      $request_options[RequestOptions::BODY] = Json::encode(['data' => $target_identifier]);
      $response = $this->request('PATCH', $url, $request_options);
      $this->assertResourceErrorResponse(400, 'Invalid body payload for the relationship.', $url, $response, FALSE);

      // Test POST: missing the 'type' field.
      $request_options[RequestOptions::BODY] = Json::encode(['data' => array_intersect_key($target_identifier, ['id' => 'id'])]);
      $response = $this->request('POST', $url, $request_options);
      $this->assertResourceErrorResponse(400, 'Invalid body payload for the relationship.', $url, $response, FALSE);
      // Test PATCH: missing the 'type' field.
      $request_options[RequestOptions::BODY] = Json::encode(['data' => array_intersect_key($target_identifier, ['id' => 'id'])]);
      $response = $this->request('PATCH', $url, $request_options);
      $this->assertResourceErrorResponse(400, 'Invalid body payload for the relationship.', $url, $response, FALSE);

      // If the base resource type is the same as that of the target's (as it
      // will be for `user--user`), then the validity error will not be
      // triggered, needlessly failing this assertion.
      if (static::$resourceTypeName !== $target_identifier['type']) {
        // Test POST: invalid target.
        $request_options[RequestOptions::BODY] = Json::encode(['data' => [$resource_identifier]]);
        $response = $this->request('POST', $url, $request_options);
        $this->assertResourceErrorResponse(400, sprintf('The provided type (%s) does not mach the destination resource types (%s).', $resource_identifier['type'], $target_identifier['type']), $url, $response, FALSE);
        // Test PATCH: invalid target.
        $request_options[RequestOptions::BODY] = Json::encode(['data' => [$resource_identifier]]);
        $response = $this->request('POST', $url, $request_options);
        $this->assertResourceErrorResponse(400, sprintf('The provided type (%s) does not mach the destination resource types (%s).', $resource_identifier['type'], $target_identifier['type']), $url, $response, FALSE);
      }

      // Test POST: duplicate targets, no arity.
      $request_options[RequestOptions::BODY] = Json::encode(['data' => [$target_identifier, $target_identifier]]);
      $response = $this->request('POST', $url, $request_options);
      $this->assertResourceErrorResponse(400, 'Duplicate relationships are not permitted. Use `meta.arity` to distinguish resource identifiers with matching `type` and `id` values.', $url, $response, FALSE);

      // Test PATCH: duplicate targets, no arity.
      $request_options[RequestOptions::BODY] = Json::encode(['data' => [$target_identifier, $target_identifier]]);
      $response = $this->request('PATCH', $url, $request_options);
      $this->assertResourceErrorResponse(400, 'Duplicate relationships are not permitted. Use `meta.arity` to distinguish resource identifiers with matching `type` and `id` values.', $url, $response, FALSE);

      // Test POST: success.
      $request_options[RequestOptions::BODY] = Json::encode(['data' => [$target_identifier]]);
      $response = $this->request('POST', $url, $request_options);
      $this->assertResourceResponse(204, NULL, $response);

      // Test POST: success, relationship already exists, no arity.
      $response = $this->request('POST', $url, $request_options);
      $this->assertResourceResponse(204, NULL, $response);

      // Test POST: success, relationship already exists, new arity.
      $request_options[RequestOptions::BODY] = Json::encode(['data' => [$target_identifier + ['meta' => ['arity' => 1]]]]);
      $response = $this->request('POST', $url, $request_options);
      $resource->set($relationship_field_name, [$target_resource, $target_resource]);
      $expected_document = $this->getExpectedGetRelationshipDocument($relationship_field_name, $resource);
      $expected_document['data'][0] += ['meta' => ['arity' => 0]];
      $expected_document['data'][1] += ['meta' => ['arity' => 1]];
      $this->assertResourceResponse(200, $expected_document, $response);

      // Test PATCH: success, new value is the same as given value.
      $request_options[RequestOptions::BODY] = Json::encode([
        'data' => [
          $target_identifier + ['meta' => ['arity' => 0]],
          $target_identifier + ['meta' => ['arity' => 1]],
        ],
      ]);
      $response = $this->request('PATCH', $url, $request_options);
      $this->assertResourceResponse(204, NULL, $response);

      // Test POST: success, relationship already exists, new arity.
      $request_options[RequestOptions::BODY] = Json::encode([
        'data' => [
          $target_identifier + ['meta' => ['arity' => 2]],
        ],
      ]);
      $response = $this->request('POST', $url, $request_options);
      $resource->set($relationship_field_name, [
        $target_resource,
        $target_resource,
        $target_resource,
      ]);
      $expected_document = $this->getExpectedGetRelationshipDocument($relationship_field_name, $resource);
      $expected_document['data'][0] += ['meta' => ['arity' => 0]];
      $expected_document['data'][1] += ['meta' => ['arity' => 1]];
      $expected_document['data'][2] += ['meta' => ['arity' => 2]];
      // 200 with response body because the request did not include the
      // existing relationship resource identifier object.
      $this->assertResourceResponse(200, $expected_document, $response);

      // Test POST: success.
      $request_options[RequestOptions::BODY] = Json::encode([
        'data' => [
          $target_identifier + ['meta' => ['arity' => 0]],
          $target_identifier + ['meta' => ['arity' => 1]],
        ],
      ]);
      $response = $this->request('POST', $url, $request_options);
      // 200 with response body because the request did not include the
      // resource identifier with arity 2.
      $this->assertResourceResponse(200, $expected_document, $response);

      // Test PATCH: success.
      $request_options[RequestOptions::BODY] = Json::encode([
        'data' => [
          $target_identifier + ['meta' => ['arity' => 0]],
          $target_identifier + ['meta' => ['arity' => 1]],
          $target_identifier + ['meta' => ['arity' => 2]],
        ],
      ]);
      $response = $this->request('PATCH', $url, $request_options);
      // 204 no content. PATCH data matches existing data.
      $this->assertResourceResponse(204, NULL, $response);

      // Test DELETE: three existing relationships, two removed.
      $request_options[RequestOptions::BODY] = Json::encode([
        'data' => [
          $target_identifier + ['meta' => ['arity' => 0]],
          $target_identifier + ['meta' => ['arity' => 2]],
        ],
      ]);
      $response = $this->request('DELETE', $url, $request_options);
      $this->assertResourceResponse(204, NULL, $response);
      // Subsequent GET should return only one resource identifier, with no
      // arity.
      $resource->set($relationship_field_name, [$target_resource]);
      $expected_document = $this->getExpectedGetRelationshipDocument($relationship_field_name, $resource);
      $response = $this->request('GET', $url, $request_options);
      $this->assertSameDocument($expected_document, Json::decode((string) $response->getBody()));

      // Test DELETE: one existing relationship, removed.
      $request_options[RequestOptions::BODY] = Json::encode(['data' => [$target_identifier]]);
      $response = $this->request('DELETE', $url, $request_options);
      $resource->set($relationship_field_name, []);
      $this->assertResourceResponse(204, NULL, $response);
      $expected_document = $this->getExpectedGetRelationshipDocument($relationship_field_name, $resource);
      $response = $this->request('GET', $url, $request_options);
      $this->assertSameDocument($expected_document, Json::decode((string) $response->getBody()));

      // Test DELETE: no existing relationships, no op, success.
      $request_options[RequestOptions::BODY] = Json::encode(['data' => [$target_identifier]]);
      $response = $this->request('DELETE', $url, $request_options);
      $this->assertResourceResponse(204, NULL, $response);
      $expected_document = $this->getExpectedGetRelationshipDocument($relationship_field_name, $resource);
      $response = $this->request('GET', $url, $request_options);
      $this->assertSameDocument($expected_document, Json::decode((string) $response->getBody()));

      // Test PATCH: success, new value is different than existing value.
      $request_options[RequestOptions::BODY] = Json::encode([
        'data' => [
          $target_identifier + ['meta' => ['arity' => 2]],
          $target_identifier + ['meta' => ['arity' => 3]],
        ],
      ]);
      $response = $this->request('PATCH', $url, $request_options);
      $resource->set($relationship_field_name, [$target_resource, $target_resource]);
      $expected_document = $this->getExpectedGetRelationshipDocument($relationship_field_name, $resource);
      $expected_document['data'][0] += ['meta' => ['arity' => 0]];
      $expected_document['data'][1] += ['meta' => ['arity' => 1]];
      // 200 with response body because arity values are computed; that means
      // that the PATCH arity values 2 + 3 will become 0 + 1 if there are not
      // already resource identifiers with those arity values.
      $this->assertResourceResponse(200, $expected_document, $response);

      // Test DELETE: two existing relationships, both removed because no arity
      // was specified.
      $request_options[RequestOptions::BODY] = Json::encode(['data' => [$target_identifier]]);
      $response = $this->request('DELETE', $url, $request_options);
      $resource->set($relationship_field_name, []);
      $this->assertResourceResponse(204, NULL, $response);
      $resource->set($relationship_field_name, []);
      $expected_document = $this->getExpectedGetRelationshipDocument($relationship_field_name, $resource);
      $response = $this->request('GET', $url, $request_options);
      $this->assertSameDocument($expected_document, Json::decode((string) $response->getBody()));
    }
    else {
      $request_options[RequestOptions::BODY] = Json::encode(['data' => [$target_identifier]]);
      $response = $this->request('POST', $url, $request_options);
      $message = 'The current user is not allowed to edit this relationship.';
      $message .= ($reason = $update_access->getReason()) ? ' ' . $reason : '';
      $this->assertResourceErrorResponse(403, $message, $url, $response, FALSE);
      $response = $this->request('PATCH', $url, $request_options);
      $this->assertResourceErrorResponse(403, $message, $url, $response, FALSE);
      $response = $this->request('DELETE', $url, $request_options);
      $this->assertResourceErrorResponse(403, $message, $url, $response, FALSE);
    }

    // Remove the test entities that were created.
    $resource->delete();
    $target_resource->delete();
  }

  /**
   * Gets an expected ResourceResponse for the given relationship.
   *
   * @param string $relationship_field_name
   *   The relationship for which to get an expected response.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   (optional) The entity for which to get expected relationship response.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The expected ResourceResponse.
   */
  protected function getExpectedGetRelationshipResponse($relationship_field_name, EntityInterface $entity = NULL) {
    $entity = $entity ?: $this->entity;
    $access = AccessResult::neutral()->addCacheContexts($entity->getEntityType()->isRevisionable() ? ['url.query_args:resourceVersion'] : []);
    $access = $access->orIf(static::entityFieldAccess($entity, $this->resourceType->getInternalName($relationship_field_name), 'view', $this->account));
    if (!$access->isAllowed()) {
      $via_link = Url::fromRoute(
        sprintf('jsonapi.%s.%s.relationship.get', static::$resourceTypeName, $relationship_field_name),
        ['entity' => $entity->uuid()]
      );
      return static::getAccessDeniedResponse($this->entity, $access, $via_link, $relationship_field_name, 'The current user is not allowed to view this relationship.', FALSE);
    }
    $expected_document = $this->getExpectedGetRelationshipDocument($relationship_field_name, $entity);
    $expected_cacheability = (new CacheableMetadata())
      ->addCacheTags(['http_response'])
      ->addCacheContexts([
        'url.site',
        'url.query_args:include',
        'url.query_args:fields',
      ])
      ->addCacheableDependency($entity)
      ->addCacheableDependency($access);
    $status_code = isset($expected_document['errors'][0]['status']) ? $expected_document['errors'][0]['status'] : 200;
    $resource_response = new ResourceResponse($expected_document, $status_code);
    $resource_response->addCacheableDependency($expected_cacheability);
    return $resource_response;
  }

  /**
   * Gets an expected document for the given relationship.
   *
   * @param string $relationship_field_name
   *   The relationship for which to get an expected response.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   (optional) The entity for which to get expected relationship document.
   *
   * @return array
   *   The expected document array.
   */
  protected function getExpectedGetRelationshipDocument($relationship_field_name, EntityInterface $entity = NULL) {
    $entity = $entity ?: $this->entity;
    $entity_type_id = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $id = $entity->uuid();
    $self_link = Url::fromUri("base:/jsonapi/$entity_type_id/$bundle/$id/relationships/$relationship_field_name")->setAbsolute();
    $related_link = Url::fromUri("base:/jsonapi/$entity_type_id/$bundle/$id/$relationship_field_name")->setAbsolute();
    if (static::$resourceTypeIsVersionable) {
      assert($entity instanceof RevisionableInterface);
      $version_query = ['resourceVersion' => 'id:' . $entity->getRevisionId()];
      $self_link->setOption('query', $version_query);
      $related_link->setOption('query', $version_query);
    }
    $data = $this->getExpectedGetRelationshipDocumentData($relationship_field_name, $entity);
    return [
      'data' => $data,
      'jsonapi' => static::$jsonApiMember,
      'links' => [
        'self' => ['href' => $self_link->toString(TRUE)->getGeneratedUrl()],
        'related' => ['href' => $related_link->toString(TRUE)->getGeneratedUrl()],
      ],
    ];
  }

  /**
   * Gets the expected document data for the given relationship.
   *
   * @param string $relationship_field_name
   *   The relationship for which to get an expected response.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   (optional) The entity for which to get expected relationship data.
   *
   * @return mixed
   *   The expected document data.
   */
  protected function getExpectedGetRelationshipDocumentData($relationship_field_name, EntityInterface $entity = NULL) {
    $entity = $entity ?: $this->entity;
    $internal_field_name = $this->resourceType->getInternalName($relationship_field_name);
    /* @var \Drupal\Core\Field\FieldItemListInterface $field */
    $field = $entity->{$internal_field_name};
    $is_multiple = $field->getFieldDefinition()->getFieldStorageDefinition()->getCardinality() !== 1;
    if ($field->isEmpty()) {
      return $is_multiple ? [] : NULL;
    }
    if (!$is_multiple) {
      $target_entity = $field->entity;
      return is_null($target_entity) ? NULL : static::toResourceIdentifier($target_entity);
    }
    else {
      return array_filter(array_map(function ($item) {
        $target_entity = $item->entity;
        return is_null($target_entity) ? NULL : static::toResourceIdentifier($target_entity);
      }, iterator_to_array($field)));
    }
  }

  /**
   * Builds an array of expected related ResourceResponses, keyed by field name.
   *
   * @param array $relationship_field_names
   *   The relationship field names for which to build expected
   *   ResourceResponses.
   * @param array $request_options
   *   Request options to apply.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   (optional) The entity for which to get expected related resources.
   *
   * @return \Drupal\jsonapi\ResourceResponse[]
   *   An array of expected ResourceResponses, keyed by their relationship field
   *   name.
   *
   * @see \GuzzleHttp\ClientInterface::request()
   */
  protected function getExpectedRelatedResponses(array $relationship_field_names, array $request_options, EntityInterface $entity = NULL) {
    $entity = $entity ?: $this->entity;
    return array_map(function ($relationship_field_name) use ($entity, $request_options) {
      return $this->getExpectedRelatedResponse($relationship_field_name, $request_options, $entity);
    }, array_combine($relationship_field_names, $relationship_field_names));
  }

  /**
   * Builds an expected related ResourceResponse for the given field.
   *
   * @param string $relationship_field_name
   *   The relationship field name for which to build an expected
   *   ResourceResponse.
   * @param array $request_options
   *   Request options to apply.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to get expected related resources.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   An expected ResourceResponse.
   *
   * @see \GuzzleHttp\ClientInterface::request()
   */
  protected function getExpectedRelatedResponse($relationship_field_name, array $request_options, EntityInterface $entity) {
    // Get the relationships responses which contain resource identifiers for
    // every related resource.
    /* @var \Drupal\jsonapi\ResourceResponse[] $relationship_responses */
    $base_resource_identifier = static::toResourceIdentifier($entity);
    $internal_name = $this->resourceType->getInternalName($relationship_field_name);
    $access = AccessResult::neutral()->addCacheContexts($entity->getEntityType()->isRevisionable() ? ['url.query_args:resourceVersion'] : []);
    $access = $access->orIf(static::entityFieldAccess($entity, $internal_name, 'view', $this->account));
    if (!$access->isAllowed()) {
      $detail = 'The current user is not allowed to view this relationship.';
      if (!$entity->access('view') && $entity->access('view label') && $access instanceof AccessResultReasonInterface && empty($access->getReason())) {
        $access->setReason("The user only has authorization for the 'view label' operation.");
      }
      $via_link = Url::fromRoute(
        sprintf('jsonapi.%s.%s.related', $base_resource_identifier['type'], $relationship_field_name),
        ['entity' => $base_resource_identifier['id']]
      );
      $related_response = static::getAccessDeniedResponse($entity, $access, $via_link, $relationship_field_name, $detail, FALSE);
    }
    else {
      $self_link = static::getRelatedLink($base_resource_identifier, $relationship_field_name);
      $relationship_response = $this->getExpectedGetRelationshipResponse($relationship_field_name, $entity);
      $relationship_document = $relationship_response->getResponseData();
      // The relationships may be empty, in which case we shouldn't attempt to
      // fetch the individual identified resources.
      if (empty($relationship_document['data'])) {
        $cache_contexts = Cache::mergeContexts([
          // Cache contexts for JSON:API URL query parameters.
          'url.query_args:fields',
          'url.query_args:include',
          // Drupal defaults.
          'url.site',
        ], $this->entity->getEntityType()->isRevisionable() ? ['url.query_args:resourceVersion'] : []);
        $cacheability = (new CacheableMetadata())->addCacheContexts($cache_contexts)->addCacheTags(['http_response']);
        $related_response = isset($relationship_document['errors'])
          ? $relationship_response
          : (new ResourceResponse(static::getEmptyCollectionResponse(!is_null($relationship_document['data']), $self_link)->getResponseData()))->addCacheableDependency($cacheability);
      }
      else {
        $is_to_one_relationship = static::isResourceIdentifier($relationship_document['data']);
        $resource_identifiers = $is_to_one_relationship
          ? [$relationship_document['data']]
          : $relationship_document['data'];
        // Remove any relationships to 'virtual' resources.
        $resource_identifiers = array_filter($resource_identifiers, function ($resource_identifier) {
          return $resource_identifier['id'] !== 'virtual';
        });
        if (!empty($resource_identifiers)) {
          $individual_responses = static::toResourceResponses($this->getResponses(static::getResourceLinks($resource_identifiers), $request_options));
          $related_response = static::toCollectionResourceResponse($individual_responses, $self_link, !$is_to_one_relationship);
        }
        else {
          $related_response = static::getEmptyCollectionResponse(!$is_to_one_relationship, $self_link);
        }
      }
      $related_response->addCacheableDependency($relationship_response->getCacheableMetadata());
    }
    return $related_response;
  }

  /**
   * Tests POSTing an individual resource, plus edge cases to ensure good DX.
   */
  public function testPostIndividual() {
    // @todo Remove this in https://www.drupal.org/node/2300677.
    if ($this->entity instanceof ConfigEntityInterface) {
      $this->assertTrue(TRUE, 'POSTing config entities is not yet supported.');
      return;
    }

    // Try with all of the following request bodies.
    $unparseable_request_body = '!{>}<';
    $parseable_valid_request_body = Json::encode($this->getPostDocument());
    /* $parseable_valid_request_body_2 = Json::encode($this->getNormalizedPostEntity()); */
    $parseable_invalid_request_body_missing_type = Json::encode($this->removeResourceTypeFromDocument($this->getPostDocument(), 'type'));
    $parseable_invalid_request_body = Json::encode($this->makeNormalizationInvalid($this->getPostDocument(), 'label'));
    $parseable_invalid_request_body_2 = Json::encode(NestedArray::mergeDeep(['data' => ['id' => $this->randomMachineName(129)]], $this->getPostDocument()));
    $parseable_invalid_request_body_3 = Json::encode(NestedArray::mergeDeep(['data' => ['attributes' => ['field_rest_test' => $this->randomString()]]], $this->getPostDocument()));
    $parseable_invalid_request_body_4 = Json::encode(NestedArray::mergeDeep(['data' => ['attributes' => ['field_nonexistent' => $this->randomString()]]], $this->getPostDocument()));

    // The URL and Guzzle request options that will be used in this test. The
    // request options will be modified/expanded throughout this test:
    // - to first test all mistakes a developer might make, and assert that the
    //   error responses provide a good DX
    // - to eventually result in a well-formed request that succeeds.
    $url = Url::fromRoute(sprintf('jsonapi.%s.collection.post', static::$resourceTypeName));
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    // DX: 405 when read-only mode is enabled.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(405, sprintf("JSON:API is configured to accept only read operations. Site administrators can configure this at %s.", Url::fromUri('base:/admin/config/services/jsonapi')->setAbsolute()->toString(TRUE)->getGeneratedUrl()), $url, $response);
    if ($this->resourceType->isLocatable()) {
      $this->assertSame(['GET'], $response->getHeader('Allow'));
    }
    else {
      $this->assertSame([''], $response->getHeader('Allow'));
    }

    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    // DX: 415 when no Content-Type request header.
    $response = $this->request('POST', $url, $request_options);
    $this->assertSame(415, $response->getStatusCode());

    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';

    // DX: 403 when unauthorized.
    $response = $this->request('POST', $url, $request_options);
    $reason = $this->getExpectedUnauthorizedAccessMessage('POST');
    $this->assertResourceErrorResponse(403, (string) $reason, $url, $response);

    $this->setUpAuthorization('POST');

    // DX: 400 when no request body.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(400, 'Empty request body.', $url, $response, FALSE);

    $request_options[RequestOptions::BODY] = $unparseable_request_body;

    // DX: 400 when unparseable request body.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(400, 'Syntax error', $url, $response, FALSE);

    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body_missing_type;

    // DX: 400 when invalid JSON:API request body.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(400, 'Resource object must include a "type".', $url, $response, FALSE);

    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body;

    // DX: 422 when invalid entity: multiple values sent for single-value field.
    $response = $this->request('POST', $url, $request_options);
    $label_field = $this->entity->getEntityType()->hasKey('label') ? $this->entity->getEntityType()->getKey('label') : static::$labelFieldName;
    $label_field_capitalized = $this->entity->getFieldDefinition($label_field)->getLabel();
    $this->assertResourceErrorResponse(422, "$label_field: $label_field_capitalized: this field cannot hold more than 1 values.", NULL, $response, '/data/attributes/' . $label_field);

    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body_2;

    // DX: 403 when invalid entity: UUID field too long.
    // @todo Fix this in https://www.drupal.org/project/drupal/issues/2149851.
    if ($this->entity->getEntityType()->hasKey('uuid')) {
      $response = $this->request('POST', $url, $request_options);
      $this->assertResourceErrorResponse(422, "IDs should be properly generated and formatted UUIDs as described in RFC 4122.", $url, $response);
    }

    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body_3;

    // DX: 403 when entity contains field without 'edit' access.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(403, "The current user is not allowed to POST the selected field (field_rest_test).", $url, $response, '/data/attributes/field_rest_test');

    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body_4;

    // DX: 422 when request document contains non-existent field.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(422, sprintf("The attribute field_nonexistent does not exist on the %s resource type.", static::$resourceTypeName), $url, $response, FALSE);

    $request_options[RequestOptions::BODY] = $parseable_valid_request_body;

    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'text/xml';

    // DX: 415 when request body in existing but not allowed format.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(415, 'No route found that matches "Content-Type: text/xml"', $url, $response);

    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';

    // 201 for well-formed request.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceResponse(201, FALSE, $response);
    $this->assertFalse($response->hasHeader('X-Drupal-Cache'));
    // If the entity is stored, perform extra checks.
    if (get_class($this->entityStorage) !== ContentEntityNullStorage::class) {
      $created_entity = $this->entityLoadUnchanged(static::$firstCreatedEntityId);
      $uuid = $created_entity->uuid();
      // @todo Remove line below in favor of commented line in https://www.drupal.org/project/jsonapi/issues/2878463.
      $location = Url::fromRoute(sprintf('jsonapi.%s.individual', static::$resourceTypeName), ['entity' => $uuid]);
      if (static::$resourceTypeIsVersionable) {
        assert($created_entity instanceof RevisionableInterface);
        $location->setOption('query', ['resourceVersion' => 'id:' . $created_entity->getRevisionId()]);
      }
      /* $location = $this->entityStorage->load(static::$firstCreatedEntityId)->toUrl('jsonapi')->setAbsolute(TRUE)->toString(); */
      $this->assertSame([$location->setAbsolute()->toString()], $response->getHeader('Location'));

      // Assert that the entity was indeed created, and that the response body
      // contains the serialized created entity.
      $created_entity_document = $this->normalize($created_entity, $url);
      $decoded_response_body = Json::decode((string) $response->getBody());
      $this->assertSame($created_entity_document, $decoded_response_body);
      // Assert that the entity was indeed created using the POSTed values.
      foreach ($this->getPostDocument()['data']['attributes'] as $field_name => $field_normalization) {
        // If the value is an array of properties, only verify that the sent
        // properties are present, the server could be computing additional
        // properties.
        if (is_array($field_normalization)) {
          $this->assertArraySubset($field_normalization, $created_entity_document['data']['attributes'][$field_name]);
        }
        else {
          $this->assertSame($field_normalization, $created_entity_document['data']['attributes'][$field_name]);
        }
      }
      if (isset($this->getPostDocument()['data']['relationships'])) {
        foreach ($this->getPostDocument()['data']['relationships'] as $field_name => $relationship_field_normalization) {
          // POSTing relationships: 'data' is required, 'links' is optional.
          static::recursiveKsort($relationship_field_normalization);
          static::recursiveKsort($created_entity_document['data']['relationships'][$field_name]);
          $this->assertSame($relationship_field_normalization, array_diff_key($created_entity_document['data']['relationships'][$field_name], ['links' => TRUE]));
        }
      }
    }
    else {
      $this->assertFalse($response->hasHeader('Location'));
    }

    // 201 for well-formed request that creates another entity.
    // If the entity is stored, delete the first created entity (in case there
    // is a uniqueness constraint).
    if (get_class($this->entityStorage) !== ContentEntityNullStorage::class) {
      $this->entityStorage->load(static::$firstCreatedEntityId)->delete();
    }
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceResponse(201, FALSE, $response);
    $this->assertFalse($response->hasHeader('X-Drupal-Cache'));

    if ($this->entity->getEntityType()->getStorageClass() !== ContentEntityNullStorage::class && $this->entity->getEntityType()->hasKey('uuid')) {
      $second_created_entity = $this->entityStorage->load(static::$secondCreatedEntityId);
      $uuid = $second_created_entity->uuid();
      // @todo Remove line below in favor of commented line in https://www.drupal.org/project/jsonapi/issues/2878463.
      $location = Url::fromRoute(sprintf('jsonapi.%s.individual', static::$resourceTypeName), ['entity' => $uuid]);
      /* $location = $this->entityStorage->load(static::$secondCreatedEntityId)->toUrl('jsonapi')->setAbsolute(TRUE)->toString(); */
      if (static::$resourceTypeIsVersionable) {
        assert($created_entity instanceof RevisionableInterface);
        $location->setOption('query', ['resourceVersion' => 'id:' . $second_created_entity->getRevisionId()]);
      }
      $this->assertSame([$location->setAbsolute()->toString()], $response->getHeader('Location'));

      // 500 when creating an entity with a duplicate UUID.
      $doc = $this->getModifiedEntityForPostTesting();
      $doc['data']['id'] = $uuid;
      $doc['data']['attributes'][$label_field] = [['value' => $this->randomMachineName()]];
      $request_options[RequestOptions::BODY] = Json::encode($doc);

      $response = $this->request('POST', $url, $request_options);
      $this->assertResourceErrorResponse(409, 'Conflict: Entity already exists.', $url, $response, FALSE);

      // 201 when successfully creating an entity with a new UUID.
      $doc = $this->getModifiedEntityForPostTesting();
      $new_uuid = \Drupal::service('uuid')->generate();
      $doc['data']['id'] = $new_uuid;
      $doc['data']['attributes'][$label_field] = [['value' => $this->randomMachineName()]];
      $request_options[RequestOptions::BODY] = Json::encode($doc);

      $response = $this->request('POST', $url, $request_options);
      $this->assertResourceResponse(201, FALSE, $response);
      $entities = $this->entityStorage->loadByProperties([$this->uuidKey => $new_uuid]);
      $new_entity = reset($entities);
      $this->assertNotNull($new_entity);
      $new_entity->delete();
    }
    else {
      $this->assertFalse($response->hasHeader('Location'));
    }
  }

  /**
   * Tests PATCHing an individual resource, plus edge cases to ensure good DX.
   */
  public function testPatchIndividual() {
    // @todo Remove this in https://www.drupal.org/node/2300677.
    if ($this->entity instanceof ConfigEntityInterface) {
      $this->assertTrue(TRUE, 'PATCHing config entities is not yet supported.');
      return;
    }

    $prior_revision_id = (int) $this->entityLoadUnchanged($this->entity->id())->getRevisionId();

    // Patch testing requires that another entity of the same type exists.
    $this->anotherEntity = $this->createAnotherEntity('dupe');

    // Try with all of the following request bodies.
    $unparseable_request_body = '!{>}<';
    $parseable_valid_request_body = Json::encode($this->getPatchDocument());
    /* $parseable_valid_request_body_2 = Json::encode($this->getNormalizedPatchEntity()); */
    $parseable_invalid_request_body = Json::encode($this->makeNormalizationInvalid($this->getPatchDocument(), 'label'));
    $parseable_invalid_request_body_2 = Json::encode(NestedArray::mergeDeep(['data' => ['attributes' => ['field_rest_test' => $this->randomString()]]], $this->getPatchDocument()));
    // The 'field_rest_test' field does not allow 'view' access, so does not end
    // up in the JSON:API document. Even when we explicitly add it to the JSON
    // API document that we send in a PATCH request, it is considered invalid.
    $parseable_invalid_request_body_3 = Json::encode(NestedArray::mergeDeep(['data' => ['attributes' => ['field_rest_test' => $this->entity->get('field_rest_test')->getValue()]]], $this->getPatchDocument()));
    $parseable_invalid_request_body_4 = Json::encode(NestedArray::mergeDeep(['data' => ['attributes' => ['field_nonexistent' => $this->randomString()]]], $this->getPatchDocument()));
    // It is invalid to PATCH a relationship field under the attributes member.
    if ($this->entity instanceof FieldableEntityInterface && $this->entity->hasField('field_jsonapi_test_entity_ref')) {
      $parseable_invalid_request_body_5 = Json::encode(NestedArray::mergeDeep(['data' => ['attributes' => ['field_jsonapi_test_entity_ref' => ['target_id' => $this->randomString()]]]], $this->getPostDocument()));
    }

    // The URL and Guzzle request options that will be used in this test. The
    // request options will be modified/expanded throughout this test:
    // - to first test all mistakes a developer might make, and assert that the
    //   error responses provide a good DX
    // - to eventually result in a well-formed request that succeeds.
    // @todo Remove line below in favor of commented line in https://www.drupal.org/project/jsonapi/issues/2878463.
    $url = Url::fromRoute(sprintf('jsonapi.%s.individual', static::$resourceTypeName), ['entity' => $this->entity->uuid()]);
    /* $url = $this->entity->toUrl('jsonapi'); */
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    // DX: 405 when read-only mode is enabled.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(405, sprintf("JSON:API is configured to accept only read operations. Site administrators can configure this at %s.", Url::fromUri('base:/admin/config/services/jsonapi')->setAbsolute()->toString(TRUE)->getGeneratedUrl()), $url, $response);
    $this->assertSame(['GET'], $response->getHeader('Allow'));

    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    // DX: 415 when no Content-Type request header.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertsame(415, $response->getStatusCode());

    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';

    // DX: 403 when unauthorized.
    $response = $this->request('PATCH', $url, $request_options);
    $reason = $this->getExpectedUnauthorizedAccessMessage('PATCH');
    $this->assertResourceErrorResponse(403, (string) $reason, $url, $response);

    $this->setUpAuthorization('PATCH');

    // DX: 400 when no request body.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(400, 'Empty request body.', $url, $response, FALSE);

    $request_options[RequestOptions::BODY] = $unparseable_request_body;

    // DX: 400 when unparseable request body.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(400, 'Syntax error', $url, $response, FALSE);

    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body;

    // DX: 422 when invalid entity: multiple values sent for single-value field.
    $response = $this->request('PATCH', $url, $request_options);
    $label_field = $this->entity->getEntityType()->hasKey('label') ? $this->entity->getEntityType()->getKey('label') : static::$labelFieldName;
    $label_field_capitalized = $this->entity->getFieldDefinition($label_field)->getLabel();
    $this->assertResourceErrorResponse(422, "$label_field: $label_field_capitalized: this field cannot hold more than 1 values.", NULL, $response, '/data/attributes/' . $label_field);

    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body_2;

    // DX: 403 when entity contains field without 'edit' access.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(403, "The current user is not allowed to PATCH the selected field (field_rest_test).", $url, $response, '/data/attributes/field_rest_test');

    // DX: 403 when entity trying to update an entity's ID field.
    $request_options[RequestOptions::BODY] = Json::encode($this->makeNormalizationInvalid($this->getPatchDocument(), 'id'));
    $response = $this->request('PATCH', $url, $request_options);
    $id_field_name = $this->entity->getEntityType()->getKey('id');
    $this->assertResourceErrorResponse(403, "The current user is not allowed to PATCH the selected field ($id_field_name). The entity ID cannot be changed.", $url, $response, "/data/attributes/$id_field_name");

    if ($this->entity->getEntityType()->hasKey('uuid')) {
      // DX: 400 when entity trying to update an entity's UUID field.
      $request_options[RequestOptions::BODY] = Json::encode($this->makeNormalizationInvalid($this->getPatchDocument(), 'uuid'));
      $response = $this->request('PATCH', $url, $request_options);
      $this->assertResourceErrorResponse(400, sprintf("The selected entity (%s) does not match the ID in the payload (%s).", $this->entity->uuid(), $this->anotherEntity->uuid()), $url, $response, FALSE);
    }

    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body_3;

    // DX: 403 when entity contains field without 'edit' nor 'view' access, even
    // when the value for that field matches the current value. This is allowed
    // in principle, but leads to information disclosure.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(403, "The current user is not allowed to PATCH the selected field (field_rest_test).", $url, $response, '/data/attributes/field_rest_test');

    // DX: 403 when sending PATCH request with updated read-only fields.
    list($modified_entity, $original_values) = static::getModifiedEntityForPatchTesting($this->entity);
    // Send PATCH request by serializing the modified entity, assert the error
    // response, change the modified entity field that caused the error response
    // back to its original value, repeat.
    foreach (static::$patchProtectedFieldNames as $patch_protected_field_name => $reason) {
      $request_options[RequestOptions::BODY] = Json::encode($this->normalize($modified_entity, $url));
      $response = $this->request('PATCH', $url, $request_options);
      $this->assertResourceErrorResponse(403, "The current user is not allowed to PATCH the selected field (" . $patch_protected_field_name . ")." . ($reason !== NULL ? ' ' . $reason : ''), $url->setAbsolute(), $response, '/data/attributes/' . $patch_protected_field_name);
      $modified_entity->get($patch_protected_field_name)->setValue($original_values[$patch_protected_field_name]);
    }

    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body_4;

    // DX: 422 when request document contains non-existent field.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(422, sprintf("The attribute field_nonexistent does not exist on the %s resource type.", static::$resourceTypeName), $url, $response, FALSE);

    // DX: 422 when updating a relationship field under attributes.
    if (isset($parseable_invalid_request_body_5)) {
      $request_options[RequestOptions::BODY] = $parseable_invalid_request_body_5;
      $response = $this->request('PATCH', $url, $request_options);
      $this->assertResourceErrorResponse(422, "The following relationship fields were provided as attributes: [ field_jsonapi_test_entity_ref ]", $url, $response, FALSE);
    }

    // 200 for well-formed PATCH request that sends all fields (even including
    // read-only ones, but with unchanged values).
    $valid_request_body = NestedArray::mergeDeep($this->normalize($this->entity, $url), $this->getPatchDocument());
    $request_options[RequestOptions::BODY] = Json::encode($valid_request_body);
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);
    $updated_entity = $this->entityLoadUnchanged($this->entity->id());
    $this->assertSame(static::$newRevisionsShouldBeAutomatic, $prior_revision_id < (int) $updated_entity->getRevisionId());
    $prior_revision_id = (int) $updated_entity->getRevisionId();

    $request_options[RequestOptions::BODY] = $parseable_valid_request_body;
    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'text/xml';

    // DX: 415 when request body in existing but not allowed format.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertSame(415, $response->getStatusCode());

    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';

    // 200 for well-formed request.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);
    $this->assertFalse($response->hasHeader('X-Drupal-Cache'));
    // Assert that the entity was indeed updated, and that the response body
    // contains the serialized updated entity.
    $updated_entity = $this->entityLoadUnchanged($this->entity->id());
    $this->assertSame(static::$newRevisionsShouldBeAutomatic, $prior_revision_id < (int) $updated_entity->getRevisionId());
    if ($this->entity instanceof RevisionLogInterface) {
      if (static::$newRevisionsShouldBeAutomatic) {
        $this->assertNotSame((int) $this->entity->getRevisionCreationTime(), (int) $updated_entity->getRevisionCreationTime());
      }
      else {
        $this->assertSame((int) $this->entity->getRevisionCreationTime(), (int) $updated_entity->getRevisionCreationTime());
      }
    }
    $updated_entity_document = $this->normalize($updated_entity, $url);
    $this->assertSame($updated_entity_document, Json::decode((string) $response->getBody()));
    $prior_revision_id = (int) $updated_entity->getRevisionId();
    // Assert that the entity was indeed created using the PATCHed values.
    foreach ($this->getPatchDocument()['data']['attributes'] as $field_name => $field_normalization) {
      // If the value is an array of properties, only verify that the sent
      // properties are present, the server could be computing additional
      // properties.
      if (is_array($field_normalization)) {
        $this->assertArraySubset($field_normalization, $updated_entity_document['data']['attributes'][$field_name]);
      }
      else {
        $this->assertSame($field_normalization, $updated_entity_document['data']['attributes'][$field_name]);
      }
    }
    if (isset($this->getPatchDocument()['data']['relationships'])) {
      foreach ($this->getPatchDocument()['data']['relationships'] as $field_name => $relationship_field_normalization) {
        // POSTing relationships: 'data' is required, 'links' is optional.
        static::recursiveKsort($relationship_field_normalization);
        static::recursiveKsort($updated_entity_document['data']['relationships'][$field_name]);
        $this->assertSame($relationship_field_normalization, array_diff_key($updated_entity_document['data']['relationships'][$field_name], ['links' => TRUE]));
      }
    }

    // Ensure that fields do not get deleted if they're not present in the PATCH
    // request. Test this using the configurable field that we added, but which
    // is not sent in the PATCH request.
    $this->assertSame('All the faith he had had had had no effect on the outcome of his life.', $updated_entity->get('field_rest_test')->value);

    // Multi-value field: remove item 0. Then item 1 becomes item 0.
    $doc_multi_value_tests = $this->getPatchDocument();
    $doc_multi_value_tests['data']['attributes']['field_rest_test_multivalue'] = $this->entity->get('field_rest_test_multivalue')->getValue();
    $doc_remove_item = $doc_multi_value_tests;
    unset($doc_remove_item['data']['attributes']['field_rest_test_multivalue'][0]);
    $request_options[RequestOptions::BODY] = Json::encode($doc_remove_item, 'api_json');
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);
    $updated_entity = $this->entityLoadUnchanged($this->entity->id());
    $this->assertSame([0 => ['value' => 'Two']], $updated_entity->get('field_rest_test_multivalue')->getValue());
    $this->assertSame(static::$newRevisionsShouldBeAutomatic, $prior_revision_id < (int) $updated_entity->getRevisionId());
    $prior_revision_id = (int) $updated_entity->getRevisionId();

    // Multi-value field: add one item before the existing one, and one after.
    $doc_add_items = $doc_multi_value_tests;
    $doc_add_items['data']['attributes']['field_rest_test_multivalue'][2] = ['value' => 'Three'];
    $request_options[RequestOptions::BODY] = Json::encode($doc_add_items);
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);
    $expected_document = [
      0 => ['value' => 'One'],
      1 => ['value' => 'Two'],
      2 => ['value' => 'Three'],
    ];
    $updated_entity = $this->entityLoadUnchanged($this->entity->id());
    $this->assertSame($expected_document, $updated_entity->get('field_rest_test_multivalue')->getValue());
    $this->assertSame(static::$newRevisionsShouldBeAutomatic, $prior_revision_id < (int) $updated_entity->getRevisionId());
    $prior_revision_id = (int) $updated_entity->getRevisionId();

    // Finally, assert that when Content Moderation is installed, a new revision
    // is automatically created when PATCHing for entity types that have a
    // moderation handler.
    // @see \Drupal\content_moderation\Entity\Handler\ModerationHandler::onPresave()
    // @see \Drupal\content_moderation\EntityTypeInfo::$moderationHandlers
    if ($updated_entity instanceof EntityPublishedInterface) {
      $updated_entity->setPublished()->save();
    }
    $this->assertTrue($this->container->get('module_installer')->install(['content_moderation'], TRUE), 'Installed modules.');

    if (!\Drupal::service('content_moderation.moderation_information')->canModerateEntitiesOfEntityType($this->entity->getEntityType())) {
      return;
    }

    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle(static::$entityTypeId, $this->entity->bundle());
    $workflow->save();
    $this->grantPermissionsToTestedRole(['use editorial transition publish']);
    $doc_add_items['data']['attributes']['field_rest_test_multivalue'][2] = ['value' => '3'];
    $request_options[RequestOptions::BODY] = Json::encode($doc_add_items);
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);
    $expected_document = [
      0 => ['value' => 'One'],
      1 => ['value' => 'Two'],
      2 => ['value' => '3'],
    ];
    $updated_entity = $this->entityLoadUnchanged($this->entity->id());
    $this->assertSame($expected_document, $updated_entity->get('field_rest_test_multivalue')->getValue());
    if ($this->entity->getEntityType()->hasHandlerClass('moderation')) {
      $this->assertLessThan((int) $updated_entity->getRevisionId(), $prior_revision_id);
    }
    else {
      $this->assertSame(static::$newRevisionsShouldBeAutomatic, $prior_revision_id < (int) $updated_entity->getRevisionId());
    }

    // Ensure that PATCHing an entity that is not the latest revision is
    // unsupported.
    if (!$this->entity->getEntityType()->isRevisionable() || !$this->entity instanceof FieldableEntityInterface) {
      return;
    }
    assert($this->entity instanceof RevisionableInterface);

    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';
    $request_options[RequestOptions::BODY] = Json::encode([
      'data' => [
        'type' => static::$resourceTypeName,
        'id' => $this->entity->uuid(),
      ],
    ]);
    $this->setUpAuthorization('PATCH');
    $this->grantPermissionsToTestedRole([
      'use editorial transition create_new_draft',
      'use editorial transition archived_published',
      'use editorial transition published',
    ]);

    // Disallow PATCHing an entity that has a pending revision.
    $updated_entity->set('moderation_state', 'draft');
    $updated_entity->setNewRevision();
    $updated_entity->save();
    $actual_response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(400, 'Updating a resource object that has a working copy is not yet supported. See https://www.drupal.org/project/jsonapi/issues/2795279.', $url, $actual_response);

    // Allow PATCHing an unpublished default revision.
    $updated_entity->set('moderation_state', 'archived');
    $updated_entity->setNewRevision();
    $updated_entity->save();
    $actual_response = $this->request('PATCH', $url, $request_options);
    $this->assertSame(200, $actual_response->getStatusCode());

    // Allow PATCHing an unpublished default revision. (An entity that
    // transitions from archived to draft remains an unpublished default
    // revision.)
    $updated_entity->set('moderation_state', 'draft');
    $updated_entity->setNewRevision();
    $updated_entity->save();
    $actual_response = $this->request('PATCH', $url, $request_options);
    $this->assertSame(200, $actual_response->getStatusCode());

    // Allow PATCHing a published default revision.
    $updated_entity->set('moderation_state', 'published');
    $updated_entity->setNewRevision();
    $updated_entity->save();
    $actual_response = $this->request('PATCH', $url, $request_options);
    $this->assertSame(200, $actual_response->getStatusCode());
  }

  /**
   * Tests DELETEing an individual resource, plus edge cases to ensure good DX.
   */
  public function testDeleteIndividual() {
    // @todo Remove this in https://www.drupal.org/node/2300677.
    if ($this->entity instanceof ConfigEntityInterface) {
      $this->assertTrue(TRUE, 'DELETEing config entities is not yet supported.');
      return;
    }

    // The URL and Guzzle request options that will be used in this test. The
    // request options will be modified/expanded throughout this test:
    // - to first test all mistakes a developer might make, and assert that the
    //   error responses provide a good DX
    // - to eventually result in a well-formed request that succeeds.
    // @todo Remove line below in favor of commented line in https://www.drupal.org/project/jsonapi/issues/2878463.
    $url = Url::fromRoute(sprintf('jsonapi.%s.individual', static::$resourceTypeName), ['entity' => $this->entity->uuid()]);
    /* $url = $this->entity->toUrl('jsonapi'); */
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    // DX: 405 when read-only mode is enabled.
    $response = $this->request('DELETE', $url, $request_options);
    $this->assertResourceErrorResponse(405, sprintf("JSON:API is configured to accept only read operations. Site administrators can configure this at %s.", Url::fromUri('base:/admin/config/services/jsonapi')->setAbsolute()->toString(TRUE)->getGeneratedUrl()), $url, $response);
    $this->assertSame(['GET'], $response->getHeader('Allow'));

    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    // DX: 403 when unauthorized.
    $response = $this->request('DELETE', $url, $request_options);
    $reason = $this->getExpectedUnauthorizedAccessMessage('DELETE');
    $this->assertResourceErrorResponse(403, (string) $reason, $url, $response, FALSE);

    $this->setUpAuthorization('DELETE');

    // 204 for well-formed request.
    $response = $this->request('DELETE', $url, $request_options);
    $this->assertResourceResponse(204, NULL, $response);

    // DX: 404 when non-existent.
    $response = $this->request('DELETE', $url, $request_options);
    $this->assertSame(404, $response->getStatusCode());
  }

  /**
   * Recursively sorts an array by key.
   *
   * @param array $array
   *   An array to sort.
   */
  protected static function recursiveKsort(array &$array) {
    // First, sort the main array.
    ksort($array);

    // Then check for child arrays.
    foreach ($array as $key => &$value) {
      if (is_array($value)) {
        static::recursiveKsort($value);
      }
    }
  }

  /**
   * Returns Guzzle request options for authentication.
   *
   * @return array
   *   Guzzle request options to use for authentication.
   *
   * @see \GuzzleHttp\ClientInterface::request()
   */
  protected function getAuthenticationRequestOptions() {
    return [
      'headers' => [
        'Authorization' => 'Basic ' . base64_encode($this->account->name->value . ':' . $this->account->passRaw),
      ],
    ];
  }

  /**
   * Clones the given entity and modifies all PATCH-protected fields.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being tested and to modify.
   *
   * @return array
   *   Contains two items:
   *   1. The modified entity object.
   *   2. The original field values, keyed by field name.
   *
   * @internal
   */
  protected static function getModifiedEntityForPatchTesting(EntityInterface $entity) {
    $modified_entity = clone $entity;
    $original_values = [];
    foreach (array_keys(static::$patchProtectedFieldNames) as $field_name) {
      $field = $modified_entity->get($field_name);
      $original_values[$field_name] = $field->getValue();
      switch ($field->getItemDefinition()->getClass()) {
        case BooleanItem::class:
          // BooleanItem::generateSampleValue() picks either 0 or 1. So a 50%
          // chance of not picking a different value.
          $field->value = ((int) $field->value) === 1 ? '0' : '1';
          break;

        case PathItem::class:
          // PathItem::generateSampleValue() doesn't set a PID, which causes
          // PathItem::postSave() to fail. Keep the PID (and other properties),
          // just modify the alias.
          $field->alias = str_replace(' ', '-', strtolower((new Random())->sentences(3)));
          break;

        default:
          $original_field = clone $field;
          while ($field->equals($original_field)) {
            $field->generateSampleItems();
          }
          break;
      }
    }

    return [$modified_entity, $original_values];
  }

  /**
   * Gets the normalized POST entity with random values for its unique fields.
   *
   * @see ::testPostIndividual
   * @see ::getPostDocument
   *
   * @return array
   *   An array structure as returned by ::getNormalizedPostEntity().
   */
  protected function getModifiedEntityForPostTesting() {
    $document = $this->getPostDocument();

    // Ensure that all the unique fields of the entity type get a new random
    // value.
    foreach (static::$uniqueFieldNames as $field_name) {
      $field_definition = $this->entity->getFieldDefinition($field_name);
      $field_type_class = $field_definition->getItemDefinition()->getClass();
      $document['data']['attributes'][$field_name] = $field_type_class::generateSampleValue($field_definition);
    }

    return $document;
  }

  /**
   * Tests sparse field sets.
   *
   * @param \Drupal\Core\Url $url
   *   The base URL with which to test includes.
   * @param array $request_options
   *   Request options to apply.
   *
   * @see \GuzzleHttp\ClientInterface::request()
   */
  protected function doTestSparseFieldSets(Url $url, array $request_options) {
    $field_sets = $this->getSparseFieldSets();
    $expected_cacheability = new CacheableMetadata();
    foreach ($field_sets as $type => $field_set) {
      if ($type === 'all') {
        assert($this->getExpectedCacheTags($field_set) === $this->getExpectedCacheTags());
        assert($this->getExpectedCacheContexts($field_set) === $this->getExpectedCacheContexts());
      }
      $query = ['fields[' . static::$resourceTypeName . ']' => implode(',', $field_set)];
      $expected_document = $this->getExpectedDocument();
      $expected_cacheability->setCacheTags($this->getExpectedCacheTags($field_set));
      $expected_cacheability->setCacheContexts($this->getExpectedCacheContexts($field_set));
      // This tests sparse field sets on included entities.
      if (strpos($type, 'nested') === 0) {
        $this->grantPermissionsToTestedRole(['access user profiles']);
        $query['fields[user--user]'] = implode(',', $field_set);
        $query['include'] = 'uid';
        $owner = $this->entity->getOwner();
        $owner_resource = static::toResourceIdentifier($owner);
        foreach ($field_set as $field_name) {
          $owner_resource['attributes'][$field_name] = $this->serializer->normalize($owner->get($field_name)[0]->get('value'), 'api_json');
        }
        $owner_resource['links']['self']['href'] = static::getResourceLink($owner_resource);
        $expected_document['included'] = [$owner_resource];
        $expected_cacheability->addCacheableDependency($owner);
        $expected_cacheability->addCacheableDependency(static::entityAccess($owner, 'view', $this->account));
      }
      // Remove fields not in the sparse field set.
      foreach (['attributes', 'relationships'] as $member) {
        if (!empty($expected_document['data'][$member])) {
          $remaining = array_intersect_key(
            $expected_document['data'][$member],
            array_flip($field_set)
          );
          if (empty($remaining)) {
            unset($expected_document['data'][$member]);
          }
          else {
            $expected_document['data'][$member] = $remaining;
          }
        }
      }
      $url->setOption('query', $query);
      // 'self' link should include the 'fields' query param.
      $expected_document['links']['self']['href'] = $url->setAbsolute()->toString();

      $response = $this->request('GET', $url, $request_options);
      // Dynamic Page Cache MISS because cache should vary based on the 'field'
      // query param. (Or uncacheable if expensive cache context.)
      $dynamic_cache = !empty(array_intersect(['user', 'session'], $expected_cacheability->getCacheContexts())) ? 'UNCACHEABLE' : 'MISS';
      $this->assertResourceResponse(
        200,
        $expected_document,
        $response,
        $expected_cacheability->getCacheTags(),
        $expected_cacheability->getCacheContexts(),
        FALSE,
        $dynamic_cache
      );
    }
    // Test Dynamic Page Cache HIT for a query with the same field set (unless
    // expensive cache context is present).
    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response, $expected_cacheability->getCacheTags(), $expected_cacheability->getCacheContexts(), FALSE, $dynamic_cache === 'MISS' ? 'HIT' : 'UNCACHEABLE');
  }

  /**
   * Tests included resources.
   *
   * @param \Drupal\Core\Url $url
   *   The base URL with which to test includes.
   * @param array $request_options
   *   Request options to apply.
   *
   * @see \GuzzleHttp\ClientInterface::request()
   */
  protected function doTestIncluded(Url $url, array $request_options) {
    $relationship_field_names = $this->getRelationshipFieldNames($this->entity);
    // If there are no relationship fields, we can't include anything.
    if (empty($relationship_field_names)) {
      return;
    }

    $field_sets = [
      'empty' => [],
      'all' => $relationship_field_names,
    ];
    if (count($relationship_field_names) > 1) {
      $about_half_the_fields = floor(count($relationship_field_names) / 2);
      $field_sets['some'] = array_slice($relationship_field_names, $about_half_the_fields);

      $nested_includes = $this->getNestedIncludePaths();
      if (!empty($nested_includes) && !in_array($nested_includes, $field_sets)) {
        $field_sets['nested'] = $nested_includes;
      }
    }

    foreach ($field_sets as $type => $included_paths) {
      $this->grantIncludedPermissions($included_paths);
      $query = ['include' => implode(',', $included_paths)];
      $url->setOption('query', $query);
      $actual_response = $this->request('GET', $url, $request_options);
      $expected_response = $this->getExpectedIncludedResourceResponse($included_paths, $request_options);
      $expected_document = $expected_response->getResponseData();
      // Dynamic Page Cache miss because cache should vary based on the
      // 'include' query param.
      $expected_cacheability = $expected_response->getCacheableMetadata();
      // MISS or UNCACHEABLE depends on data. It must not be HIT.
      $dynamic_cache = ($expected_cacheability->getCacheMaxAge() === 0 || !empty(array_intersect(['user', 'session'], $this->getExpectedCacheContexts()))) ? 'UNCACHEABLE' : 'MISS';
      $this->assertResourceResponse(
        200,
        $expected_document,
        $actual_response,
        $expected_cacheability->getCacheTags(),
        $expected_cacheability->getCacheContexts(),
        FALSE,
        $dynamic_cache
      );
    }
  }

  /**
   * Tests individual and collection revisions.
   */
  public function testRevisions() {
    if (!$this->entity->getEntityType()->isRevisionable() || !$this->entity instanceof FieldableEntityInterface) {
      return;
    }
    assert($this->entity instanceof RevisionableInterface);

    // JSON:API will only support node and media revisions until Drupal core has
    // a generic revision access API.
    if (!static::$resourceTypeIsVersionable) {
      $this->setUpRevisionAuthorization('GET');
      $url = Url::fromRoute(sprintf('jsonapi.%s.individual', static::$resourceTypeName), ['entity' => $this->entity->uuid()])->setAbsolute();
      $url->setOption('query', ['resourceVersion' => 'id:' . $this->entity->getRevisionId()]);
      $request_options = [];
      $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
      $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());
      $response = $this->request('GET', $url, $request_options);
      $detail = 'JSON:API does not yet support resource versioning for this resource type.';
      $detail .= ' For context, see https://www.drupal.org/project/jsonapi/issues/2992833#comment-12818258.';
      $detail .= ' To contribute, see https://www.drupal.org/project/drupal/issues/2350939 and https://www.drupal.org/project/drupal/issues/2809177.';
      $expected_cache_contexts = [
        'url.path',
        'url.query_args:resourceVersion',
        'url.site',
      ];
      $this->assertResourceErrorResponse(501, $detail, $url, $response, FALSE, ['http_response'], $expected_cache_contexts);
      return;
    }

    // Add a field to modify in order to test revisions.
    FieldStorageConfig::create([
      'entity_type' => static::$entityTypeId,
      'field_name' => 'field_revisionable_number',
      'type' => 'integer',
    ])->setCardinality(1)->save();
    FieldConfig::create([
      'entity_type' => static::$entityTypeId,
      'field_name' => 'field_revisionable_number',
      'bundle' => $this->entity->bundle(),
    ])->setLabel('Revisionable text field')->setTranslatable(FALSE)->save();

    // Reload entity so that it has the new field.
    $entity = $this->entityLoadUnchanged($this->entity->id());

    // Set up test data.
    /* @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    $entity->set('field_revisionable_number', 42);
    $entity->save();
    $original_revision_id = (int) $entity->getRevisionId();

    $entity->set('field_revisionable_number', 99);
    $entity->setNewRevision();
    $entity->save();
    $latest_revision_id = (int) $entity->getRevisionId();

    // @todo Remove line below in favor of commented line in https://www.drupal.org/project/jsonapi/issues/2878463.
    $url = Url::fromRoute(sprintf('jsonapi.%s.individual', static::$resourceTypeName), ['entity' => $this->entity->uuid()])->setAbsolute();
    /* $url = $this->entity->toUrl('jsonapi'); */
    $collection_url = Url::fromRoute(sprintf('jsonapi.%s.collection', static::$resourceTypeName))->setAbsolute();
    $relationship_url = Url::fromRoute(sprintf('jsonapi.%s.%s.relationship.get', static::$resourceTypeName, 'field_jsonapi_test_entity_ref'), ['entity' => $this->entity->uuid()])->setAbsolute();
    $related_url = Url::fromRoute(sprintf('jsonapi.%s.%s.related', static::$resourceTypeName, 'field_jsonapi_test_entity_ref'), ['entity' => $this->entity->uuid()])->setAbsolute();
    $original_revision_id_url = clone $url;
    $original_revision_id_url->setOption('query', ['resourceVersion' => "id:$original_revision_id"]);
    $original_revision_id_relationship_url = clone $relationship_url;
    $original_revision_id_relationship_url->setOption('query', ['resourceVersion' => "id:$original_revision_id"]);
    $original_revision_id_related_url = clone $related_url;
    $original_revision_id_related_url->setOption('query', ['resourceVersion' => "id:$original_revision_id"]);
    $latest_revision_id_url = clone $url;
    $latest_revision_id_url->setOption('query', ['resourceVersion' => "id:$latest_revision_id"]);
    $latest_revision_id_relationship_url = clone $relationship_url;
    $latest_revision_id_relationship_url->setOption('query', ['resourceVersion' => "id:$latest_revision_id"]);
    $latest_revision_id_related_url = clone $related_url;
    $latest_revision_id_related_url->setOption('query', ['resourceVersion' => "id:$latest_revision_id"]);
    $rel_latest_version_url = clone $url;
    $rel_latest_version_url->setOption('query', ['resourceVersion' => 'rel:latest-version']);
    $rel_latest_version_relationship_url = clone $relationship_url;
    $rel_latest_version_relationship_url->setOption('query', ['resourceVersion' => 'rel:latest-version']);
    $rel_latest_version_related_url = clone $related_url;
    $rel_latest_version_related_url->setOption('query', ['resourceVersion' => 'rel:latest-version']);
    $rel_latest_version_collection_url = clone $collection_url;
    $rel_latest_version_collection_url->setOption('query', ['resourceVersion' => 'rel:latest-version']);
    $rel_working_copy_url = clone $url;
    $rel_working_copy_url->setOption('query', ['resourceVersion' => 'rel:working-copy']);
    $rel_working_copy_relationship_url = clone $relationship_url;
    $rel_working_copy_relationship_url->setOption('query', ['resourceVersion' => 'rel:working-copy']);
    $rel_working_copy_related_url = clone $related_url;
    $rel_working_copy_related_url->setOption('query', ['resourceVersion' => 'rel:working-copy']);
    $rel_working_copy_collection_url = clone $collection_url;
    $rel_working_copy_collection_url->setOption('query', ['resourceVersion' => 'rel:working-copy']);
    $rel_invalid_collection_url = clone $collection_url;
    $rel_invalid_collection_url->setOption('query', ['resourceVersion' => 'rel:invalid']);
    $revision_id_key = 'drupal_internal__' . $this->entity->getEntityType()->getKey('revision');
    $published_key = $this->entity->getEntityType()->getKey('published');
    $revision_translation_affected_key = $this->entity->getEntityType()->getKey('revision_translation_affected');

    $amend_relationship_urls = function (array &$document, $revision_id) {
      if (!empty($document['data']['relationships'])) {
        foreach ($document['data']['relationships'] as &$relationship) {
          $pattern = '/resourceVersion=id%3A\d/';
          $replacement = 'resourceVersion=' . urlencode("id:$revision_id");
          $relationship['links']['self']['href'] = preg_replace($pattern, $replacement, $relationship['links']['self']['href']);
          $relationship['links']['related']['href'] = preg_replace($pattern, $replacement, $relationship['links']['related']['href']);
        }
      }
    };

    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    // Ensure 403 forbidden on typical GET.
    $actual_response = $this->request('GET', $url, $request_options);
    $expected_cacheability = $this->getExpectedUnauthorizedAccessCacheability();
    $result = $entity->access('view', $this->account, TRUE);
    $detail = 'The current user is not allowed to GET the selected resource.';
    if ($result instanceof AccessResultReasonInterface && ($reason = $result->getReason()) && !empty($reason)) {
      $detail .= ' ' . $reason;
    }
    $this->assertResourceErrorResponse(403, $detail, $url, $actual_response, '/data', $expected_cacheability->getCacheTags(), $expected_cacheability->getCacheContexts(), FALSE, 'MISS');

    // Ensure that targeting a revision does not bypass access.
    $actual_response = $this->request('GET', $original_revision_id_url, $request_options);
    $expected_cacheability = $this->getExpectedUnauthorizedAccessCacheability();
    $detail = 'The current user is not allowed to GET the selected resource. The user does not have access to the requested version.';
    if ($result instanceof AccessResultReasonInterface && ($reason = $result->getReason()) && !empty($reason)) {
      $detail .= ' ' . $reason;
    }
    $this->assertResourceErrorResponse(403, $detail, $url, $actual_response, '/data', $expected_cacheability->getCacheTags(), $expected_cacheability->getCacheContexts(), FALSE, 'MISS');

    $this->setUpRevisionAuthorization('GET');

    // Ensure that the URL without a `resourceVersion` query parameter returns
    // the default revision. This is always the latest revision when
    // content_moderation is not installed.
    $actual_response = $this->request('GET', $url, $request_options);
    $expected_document = $this->getExpectedDocument();
    // The resource object should always links to the specific revision it
    // represents.
    $expected_document['data']['links']['self']['href'] = $latest_revision_id_url->setAbsolute()->toString();
    $amend_relationship_urls($expected_document, $latest_revision_id);
    // Resource objects always link to their specific revision by revision ID.
    $expected_document['data']['attributes'][$revision_id_key] = $latest_revision_id;
    $expected_document['data']['attributes']['field_revisionable_number'] = 99;
    $expected_cache_tags = $this->getExpectedCacheTags();
    $expected_cache_contexts = $this->getExpectedCacheContexts();
    $this->assertResourceResponse(200, $expected_document, $actual_response, $expected_cache_tags, $expected_cache_contexts, FALSE, 'MISS');
    // Fetch the same revision using its revision ID.
    $actual_response = $this->request('GET', $latest_revision_id_url, $request_options);
    // The top-level document object's `self` link should always link to the
    // request URL.
    $expected_document['links']['self']['href'] = $latest_revision_id_url->setAbsolute()->toString();
    $this->assertResourceResponse(200, $expected_document, $actual_response, $expected_cache_tags, $expected_cache_contexts, FALSE, 'MISS');
    // Ensure dynamic cache HIT on second request when using a version
    // negotiator.
    $actual_response = $this->request('GET', $latest_revision_id_url, $request_options);
    $this->assertResourceResponse(200, $expected_document, $actual_response, $expected_cache_tags, $expected_cache_contexts, FALSE, 'HIT');
    // Fetch the same revision using the `latest-version` link relation type
    // negotiator. Without content_moderation, this is always the most recent
    // revision.
    $actual_response = $this->request('GET', $rel_latest_version_url, $request_options);
    $expected_document['links']['self']['href'] = $rel_latest_version_url->setAbsolute()->toString();
    $this->assertResourceResponse(200, $expected_document, $actual_response, $expected_cache_tags, $expected_cache_contexts, FALSE, 'MISS');
    // Fetch the same revision using the `working-copy` link relation type
    // negotiator. Without content_moderation, this is always the most recent
    // revision.
    $actual_response = $this->request('GET', $rel_working_copy_url, $request_options);
    $expected_document['links']['self']['href'] = $rel_working_copy_url->setAbsolute()->toString();
    $this->assertResourceResponse(200, $expected_document, $actual_response, $expected_cache_tags, $expected_cache_contexts, FALSE, 'MISS');

    // Fetch the prior revision.
    $actual_response = $this->request('GET', $original_revision_id_url, $request_options);
    $expected_document['data']['attributes'][$revision_id_key] = $original_revision_id;
    $expected_document['data']['attributes']['field_revisionable_number'] = 42;
    $expected_document['links']['self']['href'] = $original_revision_id_url->setAbsolute()->toString();
    // The resource object should always links to the specific revision it
    // represents.
    $expected_document['data']['links']['self']['href'] = $original_revision_id_url->setAbsolute()->toString();
    $amend_relationship_urls($expected_document, $original_revision_id);
    // When the resource object is not the latest version or the working copy,
    // a link should be provided that links to those versions. Therefore, the
    // presence or absence of these links communicates the state of the resource
    // object.
    $expected_document['data']['links']['latest-version']['href'] = $rel_latest_version_url->setAbsolute()->toString();
    $expected_document['data']['links']['working-copy']['href'] = $rel_working_copy_url->setAbsolute()->toString();
    $this->assertResourceResponse(200, $expected_document, $actual_response, $expected_cache_tags, $expected_cache_contexts, FALSE, 'MISS');

    // Install content_moderation module.
    $this->assertTrue($this->container->get('module_installer')->install(['content_moderation'], TRUE), 'Installed modules.');

    // Set up an editorial workflow.
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle(static::$entityTypeId, $this->entity->bundle());
    $workflow->save();

    // Ensure the test entity has content_moderation fields attached to it.
    /* @var \Drupal\Core\Entity\FieldableEntityInterface|\Drupal\Core\Entity\TranslatableRevisionableInterface $entity */
    $entity = $this->entityStorage->load($entity->id());

    // Set the published moderation state on the test entity.
    $entity->set('moderation_state', 'published');
    $entity->setNewRevision();
    $entity->save();
    $default_revision_id = (int) $entity->getRevisionId();

    // Fetch the published revision by using the `rel` version negotiator and
    // the `latest-version` version argument. With content_moderation, this is
    // now the most recent revision where the moderation state was the 'default'
    // one.
    $actual_response = $this->request('GET', $rel_latest_version_url, $request_options);
    $expected_document['data']['attributes'][$revision_id_key] = $default_revision_id;
    $expected_document['data']['attributes']['moderation_state'] = 'published';
    $expected_document['data']['attributes'][$published_key] = TRUE;
    $expected_document['data']['attributes']['field_revisionable_number'] = 99;
    $expected_document['links']['self']['href'] = $rel_latest_version_url->toString();
    $expected_document['data']['attributes'][$revision_translation_affected_key] = $entity->isRevisionTranslationAffected();
    // The resource object now must link to the new revision.
    $default_revision_id_url = clone $url;
    $default_revision_id_url = $default_revision_id_url->setOption('query', ['resourceVersion' => "id:$default_revision_id"]);
    $expected_document['data']['links']['self']['href'] = $default_revision_id_url->setAbsolute()->toString();
    $amend_relationship_urls($expected_document, $default_revision_id);
    // Since the requested version is the latest version and working copy, there
    // should be no links.
    unset($expected_document['data']['links']['latest-version']);
    unset($expected_document['data']['links']['working-copy']);
    $this->assertResourceResponse(200, $expected_document, $actual_response, $expected_cache_tags, $expected_cache_contexts, FALSE, 'MISS');
    // Fetch the collection URL using the `latest-version` version argument.
    $actual_response = $this->request('GET', $rel_latest_version_collection_url, $request_options);
    $expected_response = $this->getExpectedCollectionResponse([$entity], $rel_latest_version_collection_url->toString(), $request_options);
    $expected_collection_document = $expected_response->getResponseData();
    $expected_cacheability = $expected_response->getCacheableMetadata();
    $this->assertResourceResponse(200, $expected_collection_document, $actual_response, $expected_cacheability->getCacheTags(), $expected_cacheability->getCacheContexts(), FALSE, 'MISS');
    // Fetch the published revision by using the `working-copy` version
    // argument. With content_moderation, this is always the most recent
    // revision regardless of moderation state.
    $actual_response = $this->request('GET', $rel_working_copy_url, $request_options);
    $expected_document['links']['self']['href'] = $rel_working_copy_url->toString();
    $this->assertResourceResponse(200, $expected_document, $actual_response, $expected_cache_tags, $expected_cache_contexts, FALSE, 'MISS');
    // Fetch the collection URL using the `working-copy` version argument.
    $actual_response = $this->request('GET', $rel_working_copy_collection_url, $request_options);
    $expected_collection_document['links']['self']['href'] = $rel_working_copy_collection_url->toString();
    $this->assertResourceResponse(200, $expected_collection_document, $actual_response, $expected_cacheability->getCacheTags(), $expected_cacheability->getCacheContexts(), FALSE, 'MISS');
    // @todo: remove the next assertion when Drupal core supports entity query access control on revisions.
    $rel_working_copy_collection_url_filtered = clone $rel_working_copy_collection_url;
    $rel_working_copy_collection_url_filtered->setOption('query', ['filter[foo]' => 'bar'] + $rel_working_copy_collection_url->getOption('query'));
    $actual_response = $this->request('GET', $rel_working_copy_collection_url_filtered, $request_options);
    $filtered_collection_expected_cache_contexts = [
      'url.path',
      'url.query_args:filter',
      'url.query_args:resourceVersion',
      'url.site',
    ];
    $this->assertResourceErrorResponse(501, 'JSON:API does not support filtering on revisions other than the latest version because a secure Drupal core API does not yet exist to do so.', $rel_working_copy_collection_url_filtered, $actual_response, FALSE, ['http_response'], $filtered_collection_expected_cache_contexts);
    // Fetch the collection URL using an invalid version identifier.
    $actual_response = $this->request('GET', $rel_invalid_collection_url, $request_options);
    $invalid_version_expected_cache_contexts = [
      'url.path',
      'url.query_args:resourceVersion',
      'url.site',
    ];
    $this->assertResourceErrorResponse(400, 'Collection resources only support the following resource version identifiers: rel:latest-version, rel:working-copy', $rel_invalid_collection_url, $actual_response, FALSE, ['4xx-response', 'http_response'], $invalid_version_expected_cache_contexts);

    // Move the entity to its draft moderation state.
    $entity->set('field_revisionable_number', 42);
    // Change a relationship field so revisions can be tested on related and
    // relationship routes.
    $new_user = $this->createUser();
    $new_user->save();
    $entity->set('field_jsonapi_test_entity_ref', ['target_id' => $new_user->id()]);
    $entity->set('moderation_state', 'draft');
    $entity->setNewRevision();
    $entity->save();
    $forward_revision_id = (int) $entity->getRevisionId();

    // The `latest-version` link should *still* reference the same revision
    // since a draft is not a default revision.
    $actual_response = $this->request('GET', $rel_latest_version_url, $request_options);
    $expected_document['links']['self']['href'] = $rel_latest_version_url->toString();
    // Since the latest version is no longer also the working copy, a
    // `working-copy` link is required to indicate that there is a forward
    // revision available.
    $expected_document['data']['links']['working-copy']['href'] = $rel_working_copy_url->setAbsolute()->toString();
    $this->assertResourceResponse(200, $expected_document, $actual_response, $expected_cache_tags, $expected_cache_contexts, FALSE, 'MISS');
    // And the same should be true for collections.
    $actual_response = $this->request('GET', $rel_latest_version_collection_url, $request_options);
    $expected_collection_document['data'][0] = $expected_document['data'];
    $expected_collection_document['links']['self']['href'] = $rel_latest_version_collection_url->toString();
    $this->assertResourceResponse(200, $expected_collection_document, $actual_response, $expected_cacheability->getCacheTags(), $expected_cacheability->getCacheContexts(), FALSE, 'MISS');
    // Ensure that the `latest-version` response is same as the default link,
    // aside from the document's `self` link.
    $actual_response = $this->request('GET', $url, $request_options);
    $expected_document['links']['self']['href'] = $url->toString();
    $this->assertResourceResponse(200, $expected_document, $actual_response, $expected_cache_tags, $expected_cache_contexts, FALSE, 'MISS');
    // And the same should be true for collections.
    $actual_response = $this->request('GET', $collection_url, $request_options);
    $expected_collection_document['links']['self']['href'] = $collection_url->toString();
    $this->assertResourceResponse(200, $expected_collection_document, $actual_response, $expected_cacheability->getCacheTags(), $expected_cacheability->getCacheContexts(), FALSE, 'MISS');
    // Now, the `working-copy` link should reference the draft revision. This
    // is significant because without content_moderation, the two responses
    // would still been the same.
    //
    // Access is checked before any special permissions are granted. This
    // asserts a 403 forbidden if the user is not allowed to see unpublished
    // content.
    $result = $entity->access('view', $this->account, TRUE);
    if (!$result->isAllowed()) {
      $actual_response = $this->request('GET', $rel_working_copy_url, $request_options);
      $expected_cacheability = $this->getExpectedUnauthorizedAccessCacheability();
      $expected_cache_tags = Cache::mergeTags($expected_cacheability->getCacheTags(), $entity->getCacheTags());
      $expected_cache_contexts = $expected_cacheability->getCacheContexts();
      $detail = 'The current user is not allowed to GET the selected resource. The user does not have access to the requested version.';
      $message = $result instanceof AccessResultReasonInterface ? trim($detail . ' ' . $result->getReason()) : $detail;
      $this->assertResourceErrorResponse(403, $message, $url, $actual_response, '/data', $expected_cache_tags, $expected_cache_contexts, FALSE, 'MISS');
      // On the collection URL, we should expect to see the draft omitted from
      // the collection.
      $actual_response = $this->request('GET', $rel_working_copy_collection_url, $request_options);
      $expected_response = static::getExpectedCollectionResponse([$entity], $rel_working_copy_collection_url->toString(), $request_options);
      $expected_collection_document = $expected_response->getResponseData();
      $expected_collection_document['data'] = [];
      $expected_cacheability = $expected_response->getCacheableMetadata();
      $access_denied_response = static::getAccessDeniedResponse($entity, $result, $url, NULL, $detail)->getResponseData();
      static::addOmittedObject($expected_collection_document, static::errorsToOmittedObject($access_denied_response['errors']));
      $this->assertResourceResponse(200, $expected_collection_document, $actual_response, $expected_cacheability->getCacheTags(), $expected_cacheability->getCacheContexts(), FALSE, 'MISS');
    }

    // Since additional permissions are required to see 'draft' entities,
    // grant those permissions.
    $this->grantPermissionsToTestedRole($this->getEditorialPermissions());

    // Now, the `working-copy` link should be latest revision and be accessible.
    $actual_response = $this->request('GET', $rel_working_copy_url, $request_options);
    $expected_document['data']['attributes'][$revision_id_key] = $forward_revision_id;
    $expected_document['data']['attributes']['moderation_state'] = 'draft';
    $expected_document['data']['attributes'][$published_key] = FALSE;
    $expected_document['data']['attributes']['field_revisionable_number'] = 42;
    $expected_document['links']['self']['href'] = $rel_working_copy_url->setAbsolute()->toString();
    $expected_document['data']['attributes'][$revision_translation_affected_key] = $entity->isRevisionTranslationAffected();
    // The resource object now must link to the forward revision.
    $forward_revision_id_url = clone $url;
    $forward_revision_id_url = $forward_revision_id_url->setOption('query', ['resourceVersion' => "id:$forward_revision_id"]);
    $expected_document['data']['links']['self']['href'] = $forward_revision_id_url->setAbsolute()->toString();
    $amend_relationship_urls($expected_document, $forward_revision_id);
    // Since the the working copy is not the default revision. A
    // `latest-version` link is required to indicate that the requested version
    // is not the default revision.
    unset($expected_document['data']['links']['working-copy']);
    $expected_document['data']['links']['latest-version']['href'] = $rel_latest_version_url->setAbsolute()->toString();
    $expected_cache_tags = $this->getExpectedCacheTags();
    $expected_cache_contexts = $this->getExpectedCacheContexts();
    $this->assertResourceResponse(200, $expected_document, $actual_response, $expected_cache_tags, $expected_cache_contexts, FALSE, 'MISS');
    // And the collection response should also have the latest revision.
    $actual_response = $this->request('GET', $rel_working_copy_collection_url, $request_options);
    $expected_response = static::getExpectedCollectionResponse([$entity], $rel_working_copy_collection_url->toString(), $request_options);
    $expected_collection_document = $expected_response->getResponseData();
    $expected_collection_document['data'] = [$expected_document['data']];
    $expected_cacheability = $expected_response->getCacheableMetadata();
    $this->assertResourceResponse(200, $expected_collection_document, $actual_response, $expected_cacheability->getCacheTags(), $expected_cacheability->getCacheContexts(), FALSE, 'MISS');

    // Test relationship responses.
    // Fetch the prior revision's relationship URL.
    $test_relationship_urls = [
      [
        NULL,
        $relationship_url,
        $related_url,
      ],
      [
        $original_revision_id,
        $original_revision_id_relationship_url,
        $original_revision_id_related_url,
      ],
      [
        $latest_revision_id,
        $latest_revision_id_relationship_url,
        $latest_revision_id_related_url,
      ],
      [
        $default_revision_id,
        $rel_latest_version_relationship_url,
        $rel_latest_version_related_url,
      ],
      [
        $forward_revision_id,
        $rel_working_copy_relationship_url,
        $rel_working_copy_related_url,
      ],
    ];
    foreach ($test_relationship_urls as $revision_case) {
      list($revision_id, $relationship_url, $related_url) = $revision_case;
      // Load the revision that will be requested.
      $this->entityStorage->resetCache([$entity->id()]);
      $revision = is_null($revision_id)
        ? $this->entityStorage->load($entity->id())
        : $this->entityStorage->loadRevision($revision_id);
      // Request the relationship resource without access to the relationship
      // field.
      $actual_response = $this->request('GET', $relationship_url, $request_options);
      $expected_response = $this->getExpectedGetRelationshipResponse('field_jsonapi_test_entity_ref', $revision);
      $expected_document = $expected_response->getResponseData();
      $expected_cacheability = $expected_response->getCacheableMetadata();
      $expected_document['errors'][0]['links']['via']['href'] = $relationship_url->toString();
      $this->assertResourceResponse(403, $expected_document, $actual_response, $expected_cacheability->getCacheTags(), $expected_cacheability->getCacheContexts());
      // Request the related route.
      $actual_response = $this->request('GET', $related_url, $request_options);
      // @todo: refactor self::getExpectedRelatedResponses() into a function which returns a single response.
      $expected_response = $this->getExpectedRelatedResponses(['field_jsonapi_test_entity_ref'], $request_options, $revision)['field_jsonapi_test_entity_ref'];
      $expected_document = $expected_response->getResponseData();
      $expected_cacheability = $expected_response->getCacheableMetadata();
      $expected_document['errors'][0]['links']['via']['href'] = $related_url->toString();
      $this->assertResourceResponse(403, $expected_document, $actual_response, $expected_cacheability->getCacheTags(), $expected_cacheability->getCacheContexts());
    }
    $this->grantPermissionsToTestedRole(['field_jsonapi_test_entity_ref view access']);
    foreach ($test_relationship_urls as $revision_case) {
      list($revision_id, $relationship_url, $related_url) = $revision_case;
      // Load the revision that will be requested.
      $this->entityStorage->resetCache([$entity->id()]);
      $revision = is_null($revision_id)
        ? $this->entityStorage->load($entity->id())
        : $this->entityStorage->loadRevision($revision_id);
      // Request the relationship resource after granting access to the
      // relationship field.
      $actual_response = $this->request('GET', $relationship_url, $request_options);
      $expected_response = $this->getExpectedGetRelationshipResponse('field_jsonapi_test_entity_ref', $revision);
      $expected_document = $expected_response->getResponseData();
      $expected_cacheability = $expected_response->getCacheableMetadata();
      $this->assertResourceResponse(200, $expected_document, $actual_response, $expected_cacheability->getCacheTags(), $expected_cacheability->getCacheContexts(), FALSE, 'MISS');
      // Request the related route.
      $actual_response = $this->request('GET', $related_url, $request_options);
      $expected_response = $this->getExpectedRelatedResponse('field_jsonapi_test_entity_ref', $request_options, $revision);
      $expected_document = $expected_response->getResponseData();
      $expected_cacheability = $expected_response->getCacheableMetadata();
      $expected_document['links']['self']['href'] = $related_url->toString();
      // MISS or UNCACHEABLE depends on data. It must not be HIT.
      $dynamic_cache = !empty(array_intersect(['user', 'session'], $expected_cacheability->getCacheContexts())) ? 'UNCACHEABLE' : 'MISS';
      $this->assertResourceResponse(200, $expected_document, $actual_response, $expected_cacheability->getCacheTags(), $expected_cacheability->getCacheContexts(), FALSE, $dynamic_cache);
    }

    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    // Ensures that PATCH and DELETE on individual resources with a
    // `resourceVersion` query parameter is not supported.
    $individual_urls = [
      $original_revision_id_url,
      $latest_revision_id_url,
      $rel_latest_version_url,
      $rel_working_copy_url,
    ];
    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';
    foreach ($individual_urls as $url) {
      foreach (['PATCH', 'DELETE'] as $method) {
        $actual_response = $this->request($method, $url, $request_options);
        $this->assertResourceErrorResponse(400, sprintf('%s requests with a `%s` query parameter are not supported.', $method, 'resourceVersion'), $url, $actual_response);
      }
    }

    // Ensures that PATCH, POST and DELETE on relationship resources with a
    // `resourceVersion` query parameter is not supported.
    $relationship_urls = [
      $original_revision_id_relationship_url,
      $latest_revision_id_relationship_url,
      $rel_latest_version_relationship_url,
      $rel_working_copy_relationship_url,
    ];
    foreach ($relationship_urls as $url) {
      foreach (['PATCH', 'POST', 'DELETE'] as $method) {
        $actual_response = $this->request($method, $url, $request_options);
        $this->assertResourceErrorResponse(400, sprintf('%s requests with a `%s` query parameter are not supported.', $method, 'resourceVersion'), $url, $actual_response);
      }
    }

    // Ensures that POST on collection resources with a `resourceVersion` query
    // parameter is not supported.
    $collection_urls = [
      $rel_latest_version_collection_url,
      $rel_working_copy_collection_url,
    ];
    foreach ($collection_urls as $url) {
      foreach (['POST'] as $method) {
        $actual_response = $this->request($method, $url, $request_options);
        $this->assertResourceErrorResponse(400, sprintf('%s requests with a `%s` query parameter are not supported.', $method, 'resourceVersion'), $url, $actual_response);
      }
    }
  }

  /**
   * Decorates the expected response with included data and cache metadata.
   *
   * This adds the expected includes to the expected document and also builds
   * the expected cacheability for those includes. It does so based of responses
   * from the related routes for individual relationships.
   *
   * @param \Drupal\jsonapi\ResourceResponse $expected_response
   *   The expected ResourceResponse.
   * @param \Drupal\jsonapi\ResourceResponse[] $related_responses
   *   The related ResourceResponses, keyed by relationship field names.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The decorated ResourceResponse.
   */
  protected static function decorateExpectedResponseForIncludedFields(ResourceResponse $expected_response, array $related_responses) {
    $expected_document = $expected_response->getResponseData();
    $expected_cacheability = $expected_response->getCacheableMetadata();
    foreach ($related_responses as $related_response) {
      $related_document = $related_response->getResponseData();
      $expected_cacheability->addCacheableDependency($related_response->getCacheableMetadata());
      $expected_cacheability->setCacheTags(array_values(array_diff($expected_cacheability->getCacheTags(), ['4xx-response'])));
      // If any of the related response documents had omitted items or errors,
      // we should later expect the document to have omitted items as well.
      if (!empty($related_document['errors'])) {
        static::addOmittedObject($expected_document, static::errorsToOmittedObject($related_document['errors']));
      }
      if (!empty($related_document['meta']['omitted'])) {
        static::addOmittedObject($expected_document, $related_document['meta']['omitted']);
      }
      if (isset($related_document['data'])) {
        $related_data = $related_document['data'];
        $related_resources = (static::isResourceIdentifier($related_data))
          ? [$related_data]
          : $related_data;
        foreach ($related_resources as $related_resource) {
          if (empty($expected_document['included']) || !static::collectionHasResourceIdentifier($related_resource, $expected_document['included'])) {
            $expected_document['included'][] = $related_resource;
          }
        }
      }
    }
    return (new ResourceResponse($expected_document))->addCacheableDependency($expected_cacheability);
  }

  /**
   * Gets the expected individual ResourceResponse for GET.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The expected individual ResourceResponse.
   */
  protected function getExpectedGetIndividualResourceResponse($status_code = 200) {
    $resource_response = new ResourceResponse($this->getExpectedDocument(), $status_code);
    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts($this->getExpectedCacheContexts());
    $cacheability->setCacheTags($this->getExpectedCacheTags());
    return $resource_response->addCacheableDependency($cacheability);
  }

  /**
   * Returns an array of sparse fields sets to test.
   *
   * @return array
   *   An array of sparse field sets (an array of field names), keyed by a label
   *   for the field set.
   */
  protected function getSparseFieldSets() {
    $field_names = array_keys($this->entity->toArray());
    $field_sets = [
      'empty' => [],
      'some' => array_slice($field_names, floor(count($field_names) / 2)),
      'all' => $field_names,
    ];
    if ($this->entity instanceof EntityOwnerInterface) {
      $field_sets['nested_empty_fieldset'] = $field_sets['empty'];
      $field_sets['nested_fieldset_with_owner_fieldset'] = ['name', 'created'];
    }
    return $field_sets;
  }

  /**
   * Gets a list of public relationship names for the resource type under test.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   (optional) The entity for which to get relationship field names.
   *
   * @return array
   *   An array of relationship field names.
   */
  protected function getRelationshipFieldNames(EntityInterface $entity = NULL) {
    $entity = $entity ?: $this->entity;
    // Only content entity types can have relationships.
    $fields = $entity instanceof ContentEntityInterface
      ? iterator_to_array($entity)
      : [];
    return array_reduce($fields, function ($field_names, $field) {
      /* @var \Drupal\Core\Field\FieldItemListInterface $field */
      if (static::isReferenceFieldDefinition($field->getFieldDefinition())) {
        $field_names[] = $this->resourceType->getPublicName($field->getName());
      }
      return $field_names;
    }, []);
  }

  /**
   * Authorize the user under test with additional permissions to view includes.
   *
   * @return array
   *   An array of special permissions to be granted for certain relationship
   *   paths where the keys are relationships paths and values are an array of
   *   permissions.
   */
  protected static function getIncludePermissions() {
    return [];
  }

  /**
   * Gets an array of permissions required to view and update any tested entity.
   *
   * @return string[]
   *   An array of permission names.
   */
  protected function getEditorialPermissions() {
    return ['view latest version', "view any unpublished content"];
  }

  /**
   * Checks access for the given operation on the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check field access.
   * @param string $operation
   *   The operation for which to check access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The AccessResult.
   */
  protected static function entityAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // The default entity access control handler assumes that permissions do not
    // change during the lifetime of a request and caches access results.
    // However, we're changing permissions during a test run and need fresh
    // results, so reset the cache.
    \Drupal::entityTypeManager()->getAccessControlHandler($entity->getEntityTypeId())->resetCache();
    return $entity->access($operation, $account, TRUE);
  }

  /**
   * Checks access for the given field operation on the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check field access.
   * @param string $field_name
   *   The field for which to check access.
   * @param string $operation
   *   The operation for which to check access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The AccessResult.
   */
  protected static function entityFieldAccess(EntityInterface $entity, $field_name, $operation, AccountInterface $account) {
    $entity_access = static::entityAccess($entity, $operation === 'edit' ? 'update' : 'view', $account);
    $field_access = $entity->{$field_name}->access($operation, $account, TRUE);
    return $entity_access->andIf($field_access);
  }

  /**
   * Gets an array of of all nested include paths to be tested.
   *
   * @param int $depth
   *   (optional) The maximum depth to which included paths should be nested.
   *
   * @return array
   *   An array of nested include paths.
   */
  protected function getNestedIncludePaths($depth = 3) {
    $get_nested_relationship_field_names = function (EntityInterface $entity, $depth, $path = "") use (&$get_nested_relationship_field_names) {
      $relationship_field_names = $this->getRelationshipFieldNames($entity);
      if ($depth > 0) {
        $paths = [];
        foreach ($relationship_field_names as $field_name) {
          $next = ($path) ? "$path.$field_name" : $field_name;
          $internal_field_name = $this->resourceType->getInternalName($field_name);
          if ($target_entity = $entity->{$internal_field_name}->entity) {
            $deep = $get_nested_relationship_field_names($target_entity, $depth - 1, $next);
            $paths = array_merge($paths, $deep);
          }
          else {
            $paths[] = $next;
          }
        }
        return $paths;
      }
      return array_map(function ($target_name) use ($path) {
        return "$path.$target_name";
      }, $relationship_field_names);
    };
    return $get_nested_relationship_field_names($this->entity, $depth);
  }

  /**
   * Determines if a given field definition is a reference field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition to inspect.
   *
   * @return bool
   *   TRUE if the field definition is found to be a reference field. FALSE
   *   otherwise.
   */
  protected static function isReferenceFieldDefinition(FieldDefinitionInterface $field_definition) {
    /* @var \Drupal\Core\Field\TypedData\FieldItemDataDefinition $item_definition */
    $item_definition = $field_definition->getItemDefinition();
    $main_property = $item_definition->getMainPropertyName();
    $property_definition = $item_definition->getPropertyDefinition($main_property);
    return $property_definition instanceof DataReferenceTargetDefinition;
  }

  /**
   * Grants authorization to view includes.
   *
   * @param string[] $include_paths
   *   An array of include paths for which to grant access.
   */
  protected function grantIncludedPermissions(array $include_paths = []) {
    $applicable_permissions = array_intersect_key(static::getIncludePermissions(), array_flip($include_paths));
    $flattened_permissions = array_unique(array_reduce($applicable_permissions, 'array_merge', []));
    // Always grant access to 'view' the test entity reference field.
    $flattened_permissions[] = 'field_jsonapi_test_entity_ref view access';
    $this->grantPermissionsToTestedRole($flattened_permissions);
  }

  /**
   * Loads an entity in the test container, ignoring the static cache.
   *
   * @param int $id
   *   The entity ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The loaded entity.
   *
   * @todo Remove this after https://www.drupal.org/project/drupal/issues/3038706 lands.
   */
  protected function entityLoadUnchanged($id) {
    $this->entityStorage->resetCache();
    return $this->entityStorage->loadUnchanged($id);
  }

}
