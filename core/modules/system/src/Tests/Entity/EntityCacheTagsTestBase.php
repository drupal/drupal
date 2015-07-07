<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityCacheTagsTestBase.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\system\Tests\Cache\PageCacheTagsTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Provides helper methods for Entity cache tags tests.
 */
abstract class EntityCacheTagsTestBase extends PageCacheTagsTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_reference', 'entity_test', 'field_test');

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
  protected function setUp() {
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
      entity_create('field_storage_config', array(
        'field_name' => 'configurable_field',
        'entity_type' => $this->entity->getEntityTypeId(),
        'type' => 'test_field',
        'settings' => array(),
      ))->save();
      entity_create('field_config', array(
        'entity_type' => $this->entity->getEntityTypeId(),
        'bundle' => $this->entity->bundle(),
        'field_name' => 'configurable_field',
        'label' => 'Configurable field',
        'settings' => array(),
      ))->save();

      // Reload the entity now that a new field has been added to it.
      $storage = $this->container
        ->get('entity.manager')
        ->getStorage($this->entity->getEntityTypeId());
      $storage->resetCache();
      $this->entity = $storage->load($this->entity->id());
    }

    // Create a referencing and a non-referencing entity.
    list(
      $this->referencingEntity,
      $this->nonReferencingEntity,
    ) = $this->createReferenceTestEntities($this->entity);
  }

  /**
   * Generates standardized entity cache tags test info.
   *
   * @param string $entity_type_label
   *   The label of the entity type whose cache tags to test.
   * @param string $group
   *   The test group.
   *
   * @return array
   *
   * @see \Drupal\simpletest\TestBase::getInfo()
   */
  protected static function generateStandardizedInfo($entity_type_label, $group) {
    return array(
      'name' => "$entity_type_label entity cache tags",
      'description' => "Test the $entity_type_label entity's cache tags.",
      'group' => $group,
    );
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
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be tested, as created by createEntity().
   *
   * @return string[]
   *   An array of the additional cache contexts.
   *
   * @see \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected function getAccessCacheContextsForEntity(EntityInterface $entity) {
    return ['user.permissions'];
  }

  /**
   * Returns the additional (non-standard) cache contexts for the tested entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be tested, as created by createEntity().
   *
   * @return string[]
   *   An array of the additional cache contexts.
   *
   * @see \Drupal\system\Tests\Entity\EntityCacheTagsTestBase::createEntity()
   */
  protected function getAdditionalCacheContextsForEntity(EntityInterface $entity) {
    return [];
  }

  /**
   * Returns the additional (non-standard) cache tags for the tested entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be tested, as created by createEntity().
   * @return array
   *   An array of the additional cache tags.
   *
   * @see \Drupal\system\Tests\Entity\EntityCacheTagsTestBase::createEntity()
   */
  protected function getAdditionalCacheTagsForEntity(EntityInterface $entity) {
    return array();
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
    $view_modes = \Drupal::entityManager()
      ->getStorage('entity_view_mode')
      ->loadByProperties(array('targetEntityType' => $entity_type));

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
   *  The entity that the referencing entity should reference.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *  An array containing a referencing entity and a non-referencing entity.
   */
  protected function createReferenceTestEntities($referenced_entity) {
    // All referencing entities should be of the type 'entity_test'.
    $entity_type = 'entity_test';

    // Create a "foo" bundle for the given entity type.
    $bundle = 'foo';
    entity_test_create_bundle($bundle, NULL, $entity_type);

    // Add a field of the given type to the given entity type's "foo" bundle.
    $field_name = $referenced_entity->getEntityTypeId() . '_reference';
    entity_create('field_storage_config', array(
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => 'entity_reference',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => array(
        'target_type' => $referenced_entity->getEntityTypeId(),
      ),
    ))->save();
    entity_create('field_config', array(
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'settings' => array(
        'handler' => 'default',
        'handler_settings' => array(
          'target_bundles' => array(
            $referenced_entity->bundle() => $referenced_entity->bundle(),
          ),
          'sort' => array('field' => '_none'),
          'auto_create' => FALSE,
        ),
      ),
    ))->save();
    if (!$this->entity->getEntityType()->hasHandlerClass('view_builder')) {
      entity_get_display($entity_type, $bundle, 'full')
        ->setComponent($field_name, array(
          'type' => 'entity_reference_label',
        ))
        ->save();
    }
    else {
      $referenced_entity_view_mode = $this->selectViewMode($this->entity->getEntityTypeId());
      entity_get_display($entity_type, $bundle, 'full')
        ->setComponent($field_name, array(
          'type' => 'entity_reference_entity_view',
          'settings' => array(
            'view_mode' => $referenced_entity_view_mode,
          ),
        ))
        ->save();
    }

    // Create an entity that does reference the entity being tested.
    $label_key = \Drupal::entityManager()->getDefinition($entity_type)->getKey('label');
    $referencing_entity = entity_create($entity_type, array(
      $label_key => 'Referencing ' . $entity_type,
      'status' => 1,
      'type' => $bundle,
      $field_name => array('target_id' => $referenced_entity->id()),
    ));
    $referencing_entity->save();

    // Create an entity that does not reference the entity being tested.
    $non_referencing_entity = entity_create($entity_type, array(
      $label_key => 'Non-referencing ' . $entity_type,
      'status' => 1,
      'type' => $bundle,
    ));
    $non_referencing_entity->save();

    return array(
      $referencing_entity,
      $non_referencing_entity,
    );
  }

  /**
   * Tests cache tags presence and invalidation of the entity when referenced.
   *
   * Tests the following cache tags:
   * - entity type view cache tag: "<entity type>_view"
   * - entity cache tag: "<entity type>:<entity ID>"
   * - entity type list cache tag: "<entity type>_list"
   * - referencing entity type view cache tag: "<referencing entity type>_view"
   * - referencing entity type cache tag: "<referencing entity type>:<referencing entity ID>"
   */
  public function testReferencedEntity() {
    $entity_type = $this->entity->getEntityTypeId();
    $referencing_entity_url = $this->referencingEntity->urlInfo('canonical');
    $non_referencing_entity_url = $this->nonReferencingEntity->urlInfo('canonical');
    $listing_url = Url::fromRoute('entity.entity_test.collection_referencing_entities', [
      'entity_reference_field_name' =>  $entity_type . '_reference',
      'referenced_entity_type' => $entity_type,
      'referenced_entity_id' => $this->entity->id(),
    ]);
    $empty_entity_listing_url = Url::fromRoute('entity.entity_test.collection_empty', ['entity_type_id' => $entity_type]);
    $nonempty_entity_listing_url = Url::fromRoute('entity.entity_test.collection_labels_alphabetically', ['entity_type_id' => $entity_type]);

    // The default cache contexts for rendered entities.
    $default_cache_contexts = ['languages:' . LanguageInterface::TYPE_INTERFACE, 'theme'];
    $entity_cache_contexts = $default_cache_contexts;

    // Cache tags present on every rendered page.
    $page_cache_tags = Cache::mergeTags(
      ['rendered'],
      // If the block module is used, the Block page display variant is used,
      // which adds the block config entity type's list cache tags.
      \Drupal::moduleHandler()->moduleExists('block') ? ['config:block_list']: []
    );
    $page_cache_tags_referencing_entity = in_array('user.permissions', $this->getAccessCacheContextsForEntity($this->referencingEntity)) ? ['config:user.role.anonymous'] : [];

    $view_cache_tag = array();
    if ($this->entity->getEntityType()->hasHandlerClass('view_builder')) {
      $view_cache_tag = \Drupal::entityManager()->getViewBuilder($entity_type)
        ->getCacheTags();
    }

    // Generate the cache tags for the (non) referencing entities.
    $referencing_entity_cache_tags = Cache::mergeTags(
      $this->referencingEntity->getCacheTags(),
      \Drupal::entityManager()->getViewBuilder('entity_test')->getCacheTags(),
      // Includes the main entity's cache tags, since this entity references it.
      $this->entity->getCacheTags(),
      $this->getAdditionalCacheTagsForEntity($this->entity),
      $view_cache_tag,
      ['rendered']
    );
    $non_referencing_entity_cache_tags = Cache::mergeTags(
      $this->nonReferencingEntity->getCacheTags(),
      \Drupal::entityManager()->getViewBuilder('entity_test')->getCacheTags(),
      ['rendered']
    );

    // Generate the cache tags for all two possible entity listing paths.
    // 1. list cache tag only (listing query has no match)
    // 2. list cache tag plus entity cache tag (listing query has a match)
    $empty_entity_listing_cache_tags = Cache::mergeTags(
      $this->entity->getEntityType()->getListCacheTags(),
      $page_cache_tags
    );
    $nonempty_entity_listing_cache_tags = Cache::mergeTags(
      $this->entity->getEntityType()->getListCacheTags(),
      $this->entity->getCacheTags(),
      $this->getAdditionalCacheTagsForEntityListing($this->entity),
      $page_cache_tags
    );

    $this->pass("Test referencing entity.", 'Debug');
    $this->verifyPageCache($referencing_entity_url, 'MISS');
    // Verify a cache hit, but also the presence of the correct cache tags.
    $this->verifyPageCache($referencing_entity_url, 'HIT', Cache::mergeTags($referencing_entity_cache_tags, $page_cache_tags, $page_cache_tags_referencing_entity));
    // Also verify the existence of an entity render cache entry.
    $cache_keys = ['entity_view', 'entity_test', $this->referencingEntity->id(), 'full'];
    $cid = $this->createCacheId($cache_keys, $entity_cache_contexts);
    $access_cache_contexts = $this->getAccessCacheContextsForEntity($this->entity);
    $redirected_cid = NULL;
    if (count($access_cache_contexts)) {
      $redirected_cid = $this->createCacheId($cache_keys, Cache::mergeContexts($entity_cache_contexts, $this->getAdditionalCacheContextsForEntity($this->referencingEntity), $access_cache_contexts));
    }
    $this->verifyRenderCache($cid, $referencing_entity_cache_tags, $redirected_cid);

    $this->pass("Test non-referencing entity.", 'Debug');
    $this->verifyPageCache($non_referencing_entity_url, 'MISS');
    // Verify a cache hit, but also the presence of the correct cache tags.
    $this->verifyPageCache($non_referencing_entity_url, 'HIT', Cache::mergeTags($non_referencing_entity_cache_tags, $page_cache_tags));
    // Also verify the existence of an entity render cache entry.
    $cache_keys = ['entity_view', 'entity_test', $this->nonReferencingEntity->id(), 'full'];
    $cid = $this->createCacheId($cache_keys, $entity_cache_contexts);
    $this->verifyRenderCache($cid, $non_referencing_entity_cache_tags);


    $this->pass("Test listing of referencing entities.", 'Debug');
    // Prime the page cache for the listing of referencing entities.
    $this->verifyPageCache($listing_url, 'MISS');
    // Verify a cache hit, but also the presence of the correct cache tags.
    $this->verifyPageCache($listing_url, 'HIT', Cache::mergeTags($referencing_entity_cache_tags, $page_cache_tags, $page_cache_tags_referencing_entity));


    $this->pass("Test empty listing.", 'Debug');
    // Prime the page cache for the empty listing.
    $this->verifyPageCache($empty_entity_listing_url, 'MISS');
    // Verify a cache hit, but also the presence of the correct cache tags.
    $this->verifyPageCache($empty_entity_listing_url, 'HIT', $empty_entity_listing_cache_tags);
    // Verify the entity type's list cache contexts are present.
    $contexts_in_header = $this->drupalGetHeader('X-Drupal-Cache-Contexts');
    $this->assertEqual(Cache::mergeContexts($default_cache_contexts, $this->getAdditionalCacheContextsForEntityListing()), empty($contexts_in_header) ? [] : explode(' ', $contexts_in_header));


    $this->pass("Test listing containing referenced entity.", 'Debug');
    // Prime the page cache for the listing containing the referenced entity.
    $this->verifyPageCache($nonempty_entity_listing_url, 'MISS', $nonempty_entity_listing_cache_tags);
    // Verify a cache hit, but also the presence of the correct cache tags.
    $this->verifyPageCache($nonempty_entity_listing_url, 'HIT', $nonempty_entity_listing_cache_tags);
    // Verify the entity type's list cache contexts are present.
    $contexts_in_header = $this->drupalGetHeader('X-Drupal-Cache-Contexts');
    $this->assertEqual(Cache::mergeContexts($default_cache_contexts, $this->getAdditionalCacheContextsForEntityListing()), empty($contexts_in_header) ? [] : explode(' ', $contexts_in_header));


    // Verify that after modifying the referenced entity, there is a cache miss
    // for every route except the one for the non-referencing entity.
    $this->pass("Test modification of referenced entity.", 'Debug');
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
    $this->pass("Test modification of referencing entity.", 'Debug');
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
    $this->pass("Test modification of non-referencing entity.", 'Debug');
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
      $this->pass("Test modification of referenced entity's '$referenced_entity_view_mode' display.", 'Debug');
      $entity_display = entity_get_display($entity_type, $this->entity->bundle(), $referenced_entity_view_mode);
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


    $bundle_entity_type = $this->entity->getEntityType()->getBundleEntityType();
    if ($bundle_entity_type !== 'bundle') {
      // Verify that after modifying the corresponding bundle entity, there is a
      // cache miss for both the referencing entity, and the listing of
      // referencing entities, but not for any other routes.
      $this->pass("Test modification of referenced entity's bundle entity.", 'Debug');
      $bundle_entity = entity_load($bundle_entity_type, $this->entity->bundle());
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
      $this->pass("Test modification of referenced entity's configurable field.", 'Debug');
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
      $this->pass("Test modification of referenced entity's configurable field.", 'Debug');
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
    $this->pass("Test invalidation of referenced entity's cache tag.", 'Debug');
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
    $this->pass("Test invalidation of referenced entity's list cache tag.", 'Debug');
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
      $this->pass("Test invalidation of referenced entity's 'view' cache tag.", 'Debug');
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
    $this->pass('Test deletion of referenced entity.', 'Debug');
    $this->entity->delete();
    $this->verifyPageCache($referencing_entity_url, 'MISS');
    $this->verifyPageCache($listing_url, 'MISS');
    $this->verifyPageCache($empty_entity_listing_url, 'MISS');
    $this->verifyPageCache($nonempty_entity_listing_url, 'MISS');
    $this->verifyPageCache($non_referencing_entity_url, 'HIT');

    // Verify cache hits.
    $referencing_entity_cache_tags = Cache::mergeTags(
      $this->referencingEntity->getCacheTags(),
      \Drupal::entityManager()->getViewBuilder('entity_test')->getCacheTags(),
      ['rendered']
    );
    $this->verifyPageCache($referencing_entity_url, 'HIT', Cache::mergeTags($referencing_entity_cache_tags, $page_cache_tags));
    $this->verifyPageCache($listing_url, 'HIT', $page_cache_tags);
    $this->verifyPageCache($empty_entity_listing_url, 'HIT', $empty_entity_listing_cache_tags);
    $this->verifyPageCache($nonempty_entity_listing_url, 'HIT', Cache::mergeTags($this->entity->getEntityType()->getListCacheTags(), $this->getAdditionalCacheTagsForEntityListing(), $page_cache_tags));
  }

  /**
   * Creates a cache ID from a list of cache keys and a set of cache contexts.
   *
   * @param string[] $keys
   *   A list of cache keys.
   * @param string[] $contexts
   *   A set of cache contexts.
   *
   * @return string
   *   The cache ID string.
   */
  protected function createCacheId(array $keys, array $contexts) {
    $cid_parts = $keys;

    $contexts = \Drupal::service('cache_contexts_manager')->convertTokensToKeys($contexts);
    $cid_parts = array_merge($cid_parts, $contexts);

    return implode(':', $cid_parts);
  }

  /**
   * Verify that a given render cache entry exists, with the correct cache tags.
   *
   * @param string $cid
   *   The render cache item ID.
   * @param array $tags
   *   An array of expected cache tags.
   * @param string|null $redirected_cid
   *   (optional) The redirected render cache item ID.
   */
  protected function verifyRenderCache($cid, array $tags, $redirected_cid = NULL) {
    // Also verify the existence of an entity render cache entry.
    $cache_entry = \Drupal::cache('render')->get($cid);
    $this->assertTrue($cache_entry, 'A render cache entry exists.');
    sort($cache_entry->tags);
    sort($tags);
    $this->assertIdentical($cache_entry->tags, $tags);
    $is_redirecting_cache_item = isset($cache_entry->data['#cache_redirect']);
    if ($redirected_cid === NULL) {
      $this->assertFalse($is_redirecting_cache_item, 'Render cache entry is not a redirect.');
      // If this is a redirecting cache item unlike we expected, log it.
      if ($is_redirecting_cache_item) {
        debug($cache_entry->data);
      }
    }
    else {
      // Verify that $cid contains a cache redirect.
      $this->assertTrue($is_redirecting_cache_item, 'Render cache entry is a redirect.');
      // If this is not a redirecting cache item unlike we expected, log it.
      if (!$is_redirecting_cache_item) {
        debug($cache_entry->data);
      }
      // Verify that the cache redirect points to the expected CID.
      $redirect_cache_metadata = $cache_entry->data['#cache'];
      $actual_redirection_cid = $this->createCacheId(
        $redirect_cache_metadata['keys'],
        $redirect_cache_metadata['contexts']
      );
      $this->assertIdentical($redirected_cid, $actual_redirection_cid);
      // Finally, verify that the redirected CID exists and has the same cache
      // tags.
      $this->verifyRenderCache($redirected_cid, $tags);
    }
  }

}
