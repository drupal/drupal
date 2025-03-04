<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\entity_test\EntityTestHelper;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\system\Functional\Cache\PageCacheTagsTestBase;
use Drupal\Tests\system\Traits\CacheTestTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Provides helper methods for Entity cache tags tests.
 */
abstract class EntityCacheTagsTestBase extends PageCacheTagsTestBase {
  use CacheTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'field_test'];

  /**
   * The main entity used for testing.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The entity instance referencing the main entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $referencingEntity;

  /**
   * The entity instance not referencing the main entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $nonReferencingEntity;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Give anonymous users permission to view test entities, so that we can
    // verify the cache tags of cached versions of test entity pages.
    $user_role = Role::load(RoleInterface::ANONYMOUS_ID);
    $user_role->grantPermission('view test entity');
    $user_role->save();

    // Create an entity.
    $this->entity = $this->createEntity();

    // If this is an entity with field UI enabled, then add a configurable
    // field. We will use this configurable field in later tests to ensure that
    // field configuration invalidate render cache entries.
    if ($this->entity->getEntityType()->get('field_ui_base_route')) {
      // Add field, so we can modify the field storage and field entities to
      // verify that changes to those indeed clear cache tags.
      FieldStorageConfig::create([
        'field_name' => 'configurable_field',
        'entity_type' => $this->entity->getEntityTypeId(),
        'type' => 'test_field',
        'settings' => [],
      ])->save();
      FieldConfig::create([
        'entity_type' => $this->entity->getEntityTypeId(),
        'bundle' => $this->entity->bundle(),
        'field_name' => 'configurable_field',
        'label' => 'Configurable field',
        'settings' => [],
      ])->save();

      // Reload the entity now that a new field has been added to it.
      $storage = $this->container
        ->get('entity_type.manager')
        ->getStorage($this->entity->getEntityTypeId());
      $storage->resetCache();
      $this->entity = $storage->load($this->entity->id());
    }

    // Create a referencing and a non-referencing entity.
    [
      $this->referencingEntity,
      $this->nonReferencingEntity,
    ] = $this->createReferenceTestEntities($this->entity);
  }

  /**
   * Creates the entity to be tested.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity to be tested.
   */
  abstract protected function createEntity();

  /**
   * Returns the access cache contexts for the tested entity.
   *
   * Only list cache contexts that aren't part of the required cache contexts.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be tested, as created by createEntity().
   *
   * @return string[]
   *   An array of the additional cache contexts.
   *
   * @see \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected function getAccessCacheContextsForEntity(EntityInterface $entity) {
    return [];
  }

  /**
   * Returns the additional (non-standard) cache contexts for the tested entity.
   *
   * Only list cache contexts that aren't part of the required cache contexts.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be tested, as created by createEntity().
   *
   * @return string[]
   *   An array of the additional cache contexts.
   *
   * @see \Drupal\Tests\system\Functional\Entity\EntityCacheTagsTestBase::createEntity()
   */
  protected function getAdditionalCacheContextsForEntity(EntityInterface $entity) {
    return [];
  }

  /**
   * Returns the additional (non-standard) cache tags for the tested entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be tested, as created by createEntity().
   *
   * @return array
   *   An array of the additional cache tags.
   *
   * @see \Drupal\Tests\system\Functional\Entity\EntityCacheTagsTestBase::createEntity()
   */
  protected function getAdditionalCacheTagsForEntity(EntityInterface $entity) {
    return [];
  }

  /**
   * Returns the additional cache tags for the tested entity's listing by type.
   *
   * @return string[]
   *   An array of the additional cache contexts.
   */
  protected function getAdditionalCacheContextsForEntityListing() {
    return [];
  }

  /**
   * Returns the additional cache tags for the tested entity's listing by type.
   *
   * Necessary when there are unavoidable default entities of this type, e.g.
   * the anonymous and administrator User entities always exist.
   *
   * @return array
   *   An array of the additional cache tags.
   */
  protected function getAdditionalCacheTagsForEntityListing() {
    return [];
  }

  /**
   * Selects the preferred view mode for the given entity type.
   *
   * Prefers 'full', picks the first one otherwise, and if none are available,
   * chooses 'default'.
   */
  protected function selectViewMode($entity_type) {
    $view_modes = \Drupal::entityTypeManager()
      ->getStorage('entity_view_mode')
      ->loadByProperties(['targetEntityType' => $entity_type]);

    if (empty($view_modes)) {
      return 'default';
    }
    else {
      // Prefer the "full" display mode.
      if (isset($view_modes[$entity_type . '.full'])) {
        return 'full';
      }
      else {
        $view_modes = array_keys($view_modes);
        return substr($view_modes[0], strlen($entity_type) + 1);
      }
    }
  }

  /**
   * Creates a referencing and a non-referencing entity for testing purposes.
   *
   * @param \Drupal\Core\Entity\EntityInterface $referenced_entity
   *   The entity that the referencing entity should reference.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array containing a referencing entity and a non-referencing entity.
   */
  protected function createReferenceTestEntities($referenced_entity) {
    // All referencing entities should be of the type 'entity_test'.
    $entity_type = 'entity_test';

    // Create a "foo" bundle for the given entity type.
    $bundle = 'foo';
    EntityTestHelper::createBundle($bundle, NULL, $entity_type);

    // Add a field of the given type to the given entity type's "foo" bundle.
    $field_name = $referenced_entity->getEntityTypeId() . '_reference';
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => 'entity_reference',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'target_type' => $referenced_entity->getEntityTypeId(),
      ],
    ])->save();
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'settings' => [
        'handler' => 'default',
        'handler_settings' => [
          'target_bundles' => [
            $referenced_entity->bundle() => $referenced_entity->bundle(),
          ],
          'sort' => ['field' => '_none'],
          'auto_create' => FALSE,
        ],
      ],
    ])->save();
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    if (!$this->entity->getEntityType()->hasHandlerClass('view_builder')) {
      $display_repository->getViewDisplay($entity_type, $bundle, 'full')
        ->setComponent($field_name, [
          'type' => 'entity_reference_label',
        ])
        ->save();
    }
    else {
      $referenced_entity_view_mode = $this->selectViewMode($this->entity->getEntityTypeId());
      $display_repository->getViewDisplay($entity_type, $bundle, 'full')
        ->setComponent($field_name, [
          'type' => 'entity_reference_entity_view',
          'settings' => [
            'view_mode' => $referenced_entity_view_mode,
          ],
        ])
        ->save();
    }

    // Create an entity that does reference the entity being tested.
    $label_key = \Drupal::entityTypeManager()->getDefinition($entity_type)->getKey('label');
    $referencing_entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create([
        $label_key => 'Referencing ' . $entity_type,
        'status' => 1,
        'type' => $bundle,
        $field_name => ['target_id' => $referenced_entity->id()],
      ]);
    $referencing_entity->save();

    // Create an entity that does not reference the entity being tested.
    $non_referencing_entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create([
        $label_key => 'Non-referencing ' . $entity_type,
        'status' => 1,
        'type' => $bundle,
      ]);
    $non_referencing_entity->save();

    return [
      $referencing_entity,
      $non_referencing_entity,
    ];
  }

  /**
   * Tests cache tags presence and invalidation of the entity when referenced.
   *
   * Tests the following cache tags:
   * - entity type view cache tag: "<entity type>_view"
   * - entity cache tag: "<entity type>:<entity ID>"
   * - entity type list cache tag: "<entity type>_list"
   * - referencing entity type view cache tag: "<referencing entity type>_view"
   * - referencing entity type cache tag: "
   *   <referencing entity type>:<referencing entity ID>"
   */
  public function testReferencedEntity(): void {
    $entity_type = $this->entity->getEntityTypeId();
    $referencing_entity_url = $this->referencingEntity->toUrl('canonical');
    $non_referencing_entity_url = $this->nonReferencingEntity->toUrl('canonical');
    $listing_url = Url::fromRoute('entity.entity_test.collection_referencing_entities', [
      'entity_reference_field_name' => $entity_type . '_reference',
      'referenced_entity_type' => $entity_type,
      'referenced_entity_id' => $this->entity->id(),
    ]);
    $empty_entity_listing_url = Url::fromRoute('entity.entity_test.collection_empty', ['entity_type_id' => $entity_type]);
    $nonempty_entity_listing_url = Url::fromRoute('entity.entity_test.collection_labels_alphabetically', ['entity_type_id' => $entity_type]);

    // The default cache contexts for rendered entities.
    $default_cache_contexts = ['languages:' . LanguageInterface::TYPE_INTERFACE, 'theme', 'user.permissions'];
    $entity_cache_contexts = Cache::mergeContexts($default_cache_contexts, ['url.site']);
    $page_cache_contexts = Cache::mergeContexts($default_cache_contexts, ['url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT, 'user.roles:authenticated']);

    // Cache tags present on every rendered page.
    // 'user.permissions' is a required cache context, and responses that vary
    // by this cache context when requested by anonymous users automatically
    // also get this cache tag, to ensure correct invalidation.
    $page_cache_tags = Cache::mergeTags(['http_response', 'rendered'], ['config:user.role.anonymous']);
    // If the block module is used, the Block page display variant is used,
    // which adds the block config entity type's list cache tags.
    $page_cache_tags = Cache::mergeTags($page_cache_tags, \Drupal::moduleHandler()->moduleExists('block') ? ['config:block_list'] : []);

    $page_cache_tags_referencing_entity = in_array('user.permissions', $this->getAccessCacheContextsForEntity($this->referencingEntity)) ? ['config:user.role.anonymous'] : [];

    $view_cache_tag = [];
    if ($this->entity->getEntityType()->hasHandlerClass('view_builder')) {
      $view_cache_tag = \Drupal::entityTypeManager()->getViewBuilder($entity_type)
        ->getCacheTags();
    }

    $context_metadata = \Drupal::service('cache_contexts_manager')->convertTokensToKeys($entity_cache_contexts);
    $cache_context_tags = $context_metadata->getCacheTags();

    // Generate the cache tags for the (non) referencing entities.
    $referencing_entity_cache_tags = Cache::mergeTags($this->referencingEntity->getCacheTags(), \Drupal::entityTypeManager()->getViewBuilder('entity_test')->getCacheTags());
    // Includes the main entity's cache tags, since this entity references it.
    $referencing_entity_cache_tags = Cache::mergeTags($referencing_entity_cache_tags, $this->entity->getCacheTags());
    $referencing_entity_cache_tags = Cache::mergeTags($referencing_entity_cache_tags, $this->getAdditionalCacheTagsForEntity($this->entity));
    $referencing_entity_cache_tags = Cache::mergeTags($referencing_entity_cache_tags, $view_cache_tag);
    $referencing_entity_cache_tags = Cache::mergeTags($referencing_entity_cache_tags, $cache_context_tags);
    $referencing_entity_cache_tags = Cache::mergeTags($referencing_entity_cache_tags, ['rendered']);

    $non_referencing_entity_cache_tags = Cache::mergeTags($this->nonReferencingEntity->getCacheTags(), \Drupal::entityTypeManager()->getViewBuilder('entity_test')->getCacheTags());
    $non_referencing_entity_cache_tags = Cache::mergeTags($non_referencing_entity_cache_tags, ['rendered']);

    // Generate the cache tags for all two possible entity listing paths.
    // 1. list cache tag only (listing query has no match)
    // 2. list cache tag plus entity cache tag (listing query has a match)
    $empty_entity_listing_cache_tags = Cache::mergeTags($this->entity->getEntityType()->getListCacheTags(), $page_cache_tags);

    $nonempty_entity_listing_cache_tags = Cache::mergeTags($this->entity->getEntityType()->getListCacheTags(), $this->entity->getCacheTags());
    $nonempty_entity_listing_cache_tags = Cache::mergeTags($nonempty_entity_listing_cache_tags, $this->getAdditionalCacheTagsForEntityListing());
    $nonempty_entity_listing_cache_tags = Cache::mergeTags($nonempty_entity_listing_cache_tags, $page_cache_tags);

    $this->verifyPageCache($referencing_entity_url, 'MISS');

    // Verify a cache hit, but also the presence of the correct cache tags.
    $expected_tags = Cache::mergeTags($referencing_entity_cache_tags, $page_cache_tags);
    $expected_tags = Cache::mergeTags($expected_tags, $page_cache_tags_referencing_entity);
    $this->verifyPageCache($referencing_entity_url, 'HIT', $expected_tags);

    // Also verify the existence of an entity render cache entry.
    $cache_keys = ['entity_view', 'entity_test', $this->referencingEntity->id(), 'full'];
    $access_cache_contexts = $this->getAccessCacheContextsForEntity($this->entity);
    $additional_cache_contexts = $this->getAdditionalCacheContextsForEntity($this->referencingEntity);
    if (count($access_cache_contexts) || count($additional_cache_contexts)) {
      $cache_contexts = Cache::mergeContexts($entity_cache_contexts, $additional_cache_contexts);
      $cache_contexts = Cache::mergeContexts($cache_contexts, $access_cache_contexts);
      $context_metadata = \Drupal::service('cache_contexts_manager')->convertTokensToKeys($cache_contexts);
      $referencing_entity_cache_tags = Cache::mergeTags($referencing_entity_cache_tags, $context_metadata->getCacheTags());
    }
    $this->verifyRenderCache($cache_keys, $referencing_entity_cache_tags, (new CacheableMetadata())->setCacheContexts($entity_cache_contexts));

    $this->verifyPageCache($non_referencing_entity_url, 'MISS');
    // Verify a cache hit, but also the presence of the correct cache tags.
    $this->verifyPageCache($non_referencing_entity_url, 'HIT', Cache::mergeTags($non_referencing_entity_cache_tags, $page_cache_tags));
    // Also verify the existence of an entity render cache entry.
    $cache_keys = ['entity_view', 'entity_test', $this->nonReferencingEntity->id(), 'full'];
    $this->verifyRenderCache($cache_keys, $non_referencing_entity_cache_tags, (new CacheableMetadata())->setCacheContexts($entity_cache_contexts));

    // Prime the page cache for the listing of referencing entities.
    $this->verifyPageCache($listing_url, 'MISS');

    // Verify a cache hit, but also the presence of the correct cache tags.
    $expected_tags = Cache::mergeTags($referencing_entity_cache_tags, $page_cache_tags);
    $expected_tags = Cache::mergeTags($expected_tags, $page_cache_tags_referencing_entity);
    $this->verifyPageCache($listing_url, 'HIT', $expected_tags);

    // Prime the page cache for the empty listing.
    $this->verifyPageCache($empty_entity_listing_url, 'MISS');
    // Verify a cache hit, but also the presence of the correct cache tags.
    $this->verifyPageCache($empty_entity_listing_url, 'HIT', $empty_entity_listing_cache_tags);
    // Verify the entity type's list cache contexts are present.
    $contexts_in_header = $this->getSession()->getResponseHeader('X-Drupal-Cache-Contexts');
    $this->assertEqualsCanonicalizing(Cache::mergeContexts($page_cache_contexts, $this->getAdditionalCacheContextsForEntityListing()), empty($contexts_in_header) ? [] : explode(' ', $contexts_in_header));

    // Prime the page cache for the listing containing the referenced entity.
    $this->verifyPageCache($nonempty_entity_listing_url, 'MISS', $nonempty_entity_listing_cache_tags);
    // Verify a cache hit, but also the presence of the correct cache tags.
    $this->verifyPageCache($nonempty_entity_listing_url, 'HIT', $nonempty_entity_listing_cache_tags);
    // Verify the entity type's list cache contexts are present.
    $contexts_in_header = $this->getSession()->getResponseHeader('X-Drupal-Cache-Contexts');
    $this->assertEqualsCanonicalizing(Cache::mergeContexts($page_cache_contexts, $this->getAdditionalCacheContextsForEntityListing()), empty($contexts_in_header) ? [] : explode(' ', $contexts_in_header));

    // Verify that after modifying the referenced entity, there is a cache miss
    // for every route except the one for the non-referencing entity.
    $this->entity->save();
    $this->verifyPageCache($referencing_entity_url, 'MISS');
    $this->verifyPageCache($listing_url, 'MISS');
    $this->verifyPageCache($empty_entity_listing_url, 'MISS');
    $this->verifyPageCache($nonempty_entity_listing_url, 'MISS');
    $this->verifyPageCache($non_referencing_entity_url, 'HIT');

    // Verify cache hits.
    $this->verifyPageCache($referencing_entity_url, 'HIT');
    $this->verifyPageCache($listing_url, 'HIT');
    $this->verifyPageCache($empty_entity_listing_url, 'HIT');
    $this->verifyPageCache($nonempty_entity_listing_url, 'HIT');

    // Verify that after modifying the referencing entity, there is a cache miss
    // for every route except the ones for the non-referencing entity and the
    // empty entity listing.
    $this->referencingEntity->save();
    $this->verifyPageCache($referencing_entity_url, 'MISS');
    $this->verifyPageCache($listing_url, 'MISS');
    $this->verifyPageCache($nonempty_entity_listing_url, 'HIT');
    $this->verifyPageCache($non_referencing_entity_url, 'HIT');
    $this->verifyPageCache($empty_entity_listing_url, 'HIT');

    // Verify cache hits.
    $this->verifyPageCache($referencing_entity_url, 'HIT');
    $this->verifyPageCache($listing_url, 'HIT');
    $this->verifyPageCache($nonempty_entity_listing_url, 'HIT');

    // Verify that after modifying the non-referencing entity, there is a cache
    // miss only for the non-referencing entity route.
    $this->nonReferencingEntity->save();
    $this->verifyPageCache($referencing_entity_url, 'HIT');
    $this->verifyPageCache($listing_url, 'HIT');
    $this->verifyPageCache($empty_entity_listing_url, 'HIT');
    $this->verifyPageCache($nonempty_entity_listing_url, 'HIT');
    $this->verifyPageCache($non_referencing_entity_url, 'MISS');

    // Verify cache hits.
    $this->verifyPageCache($non_referencing_entity_url, 'HIT');

    if ($this->entity->getEntityType()->hasHandlerClass('view_builder')) {
      // Verify that after modifying the entity's display, there is a cache miss
      // for both the referencing entity, and the listing of referencing
      // entities, but not for any other routes.
      $referenced_entity_view_mode = $this->selectViewMode($this->entity->getEntityTypeId());
      /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
      $display_repository = \Drupal::service('entity_display.repository');
      $entity_display = $display_repository->getViewDisplay($entity_type, $this->entity->bundle(), $referenced_entity_view_mode);
      $entity_display->save();
      $this->verifyPageCache($referencing_entity_url, 'MISS');
      $this->verifyPageCache($listing_url, 'MISS');
      $this->verifyPageCache($non_referencing_entity_url, 'HIT');
      $this->verifyPageCache($empty_entity_listing_url, 'HIT');
      $this->verifyPageCache($nonempty_entity_listing_url, 'HIT');

      // Verify cache hits.
      $this->verifyPageCache($referencing_entity_url, 'HIT');
      $this->verifyPageCache($listing_url, 'HIT');
    }

    if ($bundle_entity_type_id = $this->entity->getEntityType()->getBundleEntityType()) {
      // Verify that after modifying the corresponding bundle entity, there is a
      // cache miss for both the referencing entity, and the listing of
      // referencing entities, but not for any other routes.
      $bundle_entity = $this->container->get('entity_type.manager')
        ->getStorage($bundle_entity_type_id)
        ->load($this->entity->bundle());
      $bundle_entity->save();
      $this->verifyPageCache($referencing_entity_url, 'MISS');
      $this->verifyPageCache($listing_url, 'MISS');
      $this->verifyPageCache($non_referencing_entity_url, 'HIT');
      // Special case: entity types may choose to use their bundle entity type
      // cache tags, to avoid having excessively granular invalidation.
      $is_special_case = $bundle_entity->getCacheTags() == $this->entity->getCacheTags() && $bundle_entity->getEntityType()->getListCacheTags() == $this->entity->getEntityType()->getListCacheTags();
      if ($is_special_case) {
        $this->verifyPageCache($empty_entity_listing_url, 'MISS');
        $this->verifyPageCache($nonempty_entity_listing_url, 'MISS');
      }
      else {
        $this->verifyPageCache($empty_entity_listing_url, 'HIT');
        $this->verifyPageCache($nonempty_entity_listing_url, 'HIT');
      }

      // Verify cache hits.
      $this->verifyPageCache($referencing_entity_url, 'HIT');
      $this->verifyPageCache($listing_url, 'HIT');
      if ($is_special_case) {
        $this->verifyPageCache($empty_entity_listing_url, 'HIT');
        $this->verifyPageCache($nonempty_entity_listing_url, 'HIT');
      }
    }

    if ($this->entity->getEntityType()->get('field_ui_base_route')) {
      // Verify that after modifying a configurable field on the entity, there
      // is a cache miss.
      $field_storage_name = $this->entity->getEntityTypeId() . '.configurable_field';
      $field_storage = FieldStorageConfig::load($field_storage_name);
      $field_storage->save();
      $this->verifyPageCache($referencing_entity_url, 'MISS');
      $this->verifyPageCache($listing_url, 'MISS');
      $this->verifyPageCache($empty_entity_listing_url, 'HIT');
      $this->verifyPageCache($nonempty_entity_listing_url, 'HIT');
      $this->verifyPageCache($non_referencing_entity_url, 'HIT');

      // Verify cache hits.
      $this->verifyPageCache($referencing_entity_url, 'HIT');
      $this->verifyPageCache($listing_url, 'HIT');

      // Verify that after modifying a configurable field on the entity, there
      // is a cache miss.
      $field_name = $this->entity->getEntityTypeId() . '.' . $this->entity->bundle() . '.configurable_field';
      $field = FieldConfig::load($field_name);
      $field->save();
      $this->verifyPageCache($referencing_entity_url, 'MISS');
      $this->verifyPageCache($listing_url, 'MISS');
      $this->verifyPageCache($empty_entity_listing_url, 'HIT');
      $this->verifyPageCache($nonempty_entity_listing_url, 'HIT');
      $this->verifyPageCache($non_referencing_entity_url, 'HIT');

      // Verify cache hits.
      $this->verifyPageCache($referencing_entity_url, 'HIT');
      $this->verifyPageCache($listing_url, 'HIT');
    }

    // Verify that after invalidating the entity's cache tag directly, there is
    // a cache miss for every route except the ones for the non-referencing
    // entity and the empty entity listing.
    Cache::invalidateTags($this->entity->getCacheTagsToInvalidate());
    $this->verifyPageCache($referencing_entity_url, 'MISS');
    $this->verifyPageCache($listing_url, 'MISS');
    $this->verifyPageCache($nonempty_entity_listing_url, 'MISS');
    $this->verifyPageCache($non_referencing_entity_url, 'HIT');
    $this->verifyPageCache($empty_entity_listing_url, 'HIT');

    // Verify cache hits.
    $this->verifyPageCache($referencing_entity_url, 'HIT');
    $this->verifyPageCache($listing_url, 'HIT');
    $this->verifyPageCache($nonempty_entity_listing_url, 'HIT');

    // Verify that after invalidating the entity's list cache tag directly,
    // there is a cache miss for both the empty entity listing and the non-empty
    // entity listing routes, but not for other routes.
    Cache::invalidateTags($this->entity->getEntityType()->getListCacheTags());
    $this->verifyPageCache($empty_entity_listing_url, 'MISS');
    $this->verifyPageCache($nonempty_entity_listing_url, 'MISS');
    $this->verifyPageCache($referencing_entity_url, 'HIT');
    $this->verifyPageCache($non_referencing_entity_url, 'HIT');
    $this->verifyPageCache($listing_url, 'HIT');

    // Verify cache hits.
    $this->verifyPageCache($empty_entity_listing_url, 'HIT');
    $this->verifyPageCache($nonempty_entity_listing_url, 'HIT');

    if (!empty($view_cache_tag)) {
      // Verify that after invalidating the generic entity type's view cache tag
      // directly, there is a cache miss for both the referencing entity, and the
      // listing of referencing entities, but not for other routes.
      Cache::invalidateTags($view_cache_tag);
      $this->verifyPageCache($referencing_entity_url, 'MISS');
      $this->verifyPageCache($listing_url, 'MISS');
      $this->verifyPageCache($non_referencing_entity_url, 'HIT');
      $this->verifyPageCache($empty_entity_listing_url, 'HIT');
      $this->verifyPageCache($nonempty_entity_listing_url, 'HIT');

      // Verify cache hits.
      $this->verifyPageCache($referencing_entity_url, 'HIT');
      $this->verifyPageCache($listing_url, 'HIT');
    }

    // Verify that after deleting the entity, there is a cache miss for every
    // route except for the non-referencing entity one.
    $this->entity->delete();
    $this->verifyPageCache($referencing_entity_url, 'MISS');
    $this->verifyPageCache($listing_url, 'MISS');
    $this->verifyPageCache($empty_entity_listing_url, 'MISS');
    $this->verifyPageCache($nonempty_entity_listing_url, 'MISS');
    $this->verifyPageCache($non_referencing_entity_url, 'HIT');

    // Verify cache hits.
    $referencing_entity_cache_tags = Cache::mergeTags($this->referencingEntity->getCacheTags(), \Drupal::entityTypeManager()->getViewBuilder('entity_test')->getCacheTags());
    $referencing_entity_cache_tags = Cache::mergeTags($referencing_entity_cache_tags, ['http_response', 'rendered']);

    $nonempty_entity_listing_cache_tags = Cache::mergeTags($this->entity->getEntityType()->getListCacheTags(), $this->getAdditionalCacheTagsForEntityListing());
    $nonempty_entity_listing_cache_tags = Cache::mergeTags($nonempty_entity_listing_cache_tags, $page_cache_tags);

    $this->verifyPageCache($referencing_entity_url, 'HIT', Cache::mergeTags($referencing_entity_cache_tags, $page_cache_tags));
    $this->verifyPageCache($listing_url, 'HIT', $page_cache_tags);
    $this->verifyPageCache($empty_entity_listing_url, 'HIT', $empty_entity_listing_cache_tags);
    $this->verifyPageCache($nonempty_entity_listing_url, 'HIT', $nonempty_entity_listing_cache_tags);
  }

  /**
   * Retrieves the render cache backend as a variation cache.
   *
   * This is how Drupal\Core\Render\RenderCache uses the render cache backend.
   *
   * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0.
   * Use ::getRenderVariationCache() instead, which is inherited
   * from CacheTestTrait.
   *
   * @see https://www.drupal.org/node/3508905
   *
   * @return \Drupal\Core\Cache\VariationCacheInterface
   *   The render cache backend as a variation cache.
   */
  protected function getRenderCacheBackend() {
    return $this->getRenderVariationCache();
  }

}
