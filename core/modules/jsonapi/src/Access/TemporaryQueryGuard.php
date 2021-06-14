<?php

namespace Drupal\jsonapi\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\DataReferenceDefinitionInterface;
use Drupal\jsonapi\Query\EntityCondition;
use Drupal\jsonapi\Query\EntityConditionGroup;
use Drupal\jsonapi\Query\Filter;

/**
 * Adds sufficient access control to collection queries.
 *
 * This class will be removed when new Drupal core APIs have been put in place
 * to make it obsolete.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @todo These additional security measures should eventually reside in the
 *   Entity API subsystem but were introduced here to address a security
 *   vulnerability. The following two issues should obsolesce this class:
 *
 * @see https://www.drupal.org/project/drupal/issues/2809177
 * @see https://www.drupal.org/project/drupal/issues/777578
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class TemporaryQueryGuard {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected static $fieldManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected static $moduleHandler;

  /**
   * Sets the entity field manager.
   *
   * This must be called before calling ::applyAccessControls().
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   The entity field manager.
   */
  public static function setFieldManager(EntityFieldManagerInterface $field_manager) {
    static::$fieldManager = $field_manager;
  }

  /**
   * Sets the module handler.
   *
   * This must be called before calling ::applyAccessControls().
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public static function setModuleHandler(ModuleHandlerInterface $module_handler) {
    static::$moduleHandler = $module_handler;
  }

  /**
   * Applies access controls to an entity query.
   *
   * @param \Drupal\jsonapi\Query\Filter $filter
   *   The filters applicable to the query.
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query to which access controls should be applied.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheability
   *   Collects cacheability for the query.
   */
  public static function applyAccessControls(Filter $filter, QueryInterface $query, CacheableMetadata $cacheability) {
    assert(static::$fieldManager !== NULL);
    assert(static::$moduleHandler !== NULL);
    $filtered_fields = static::collectFilteredFields($filter->root());
    $field_specifiers = array_map(function ($field) {
      return explode('.', $field);
    }, $filtered_fields);
    static::secureQuery($query, $query->getEntityTypeId(), static::buildTree($field_specifiers), $cacheability);
  }

  /**
   * Applies tags, metadata and conditions to secure an entity query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query to be secured.
   * @param string $entity_type_id
   *   An entity type ID.
   * @param array $tree
   *   A tree of field specifiers in an entity query condition. The tree is a
   *   multi-dimensional array where the keys are field specifiers and the
   *   values are multi-dimensional array of the same form, containing only
   *   subsequent specifiers. @see ::buildTree().
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheability
   *   Collects cacheability for the query.
   * @param string|null $field_prefix
   *   Internal use only. Contains a string representation of the previously
   *   visited field specifiers.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage_definition
   *   Internal use only. The current field storage definition, if known.
   *
   * @see \Drupal\Core\Database\Query\AlterableInterface::addTag()
   * @see \Drupal\Core\Database\Query\AlterableInterface::addMetaData()
   * @see \Drupal\Core\Database\Query\ConditionInterface
   */
  protected static function secureQuery(QueryInterface $query, $entity_type_id, array $tree, CacheableMetadata $cacheability, $field_prefix = NULL, FieldStorageDefinitionInterface $field_storage_definition = NULL) {
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    // Config entity types are not fieldable, therefore they do not have field
    // access restrictions, nor entity references to other entity types.
    if ($entity_type instanceof ConfigEntityTypeInterface) {
      return;
    }
    foreach ($tree as $specifier => $children) {
      // The field path reconstructs the entity condition fields.
      // E.g. `uid.0` would become `uid.0.name` if $specifier === 'name'.
      $child_prefix = (is_null($field_prefix)) ? $specifier : "$field_prefix.$specifier";
      if (is_null($field_storage_definition)) {
        // When the field storage definition is NULL, this specifier is the
        // first specifier in an entity query field path or the previous
        // specifier was a data reference that has been traversed. In both
        // cases, the specifier must be a field name.
        $field_storage_definitions = static::$fieldManager->getFieldStorageDefinitions($entity_type_id);
        static::secureQuery($query, $entity_type_id, $children, $cacheability, $child_prefix, $field_storage_definitions[$specifier]);
        // When $field_prefix is NULL, this must be the first specifier in the
        // entity query field path and a condition for the query's base entity
        // type must be applied.
        if (is_null($field_prefix)) {
          static::applyAccessConditions($query, $entity_type_id, NULL, $cacheability);
        }
      }
      else {
        // When the specifier is an entity reference, it can contain an entity
        // type specifier, like so: `entity:node`. This extracts the `entity`
        // portion. JSON:API will have already validated that the property
        // exists.
        $split_specifier = explode(':', $specifier, 2);
        list($property_name, $target_entity_type_id) = array_merge($split_specifier, count($split_specifier) === 2 ? [] : [NULL]);
        // The specifier is either a field property or a delta. If it is a data
        // reference or a delta, then it needs to be traversed to the next
        // specifier. However, if the specific is a simple field property, i.e.
        // it is neither a data reference nor a delta, then there is no need to
        // evaluate the remaining specifiers.
        $property_definition = $field_storage_definition->getPropertyDefinition($property_name);
        if ($property_definition instanceof DataReferenceDefinitionInterface) {
          // Because the filter is following an entity reference, ensure
          // access is respected on those targeted entities.
          // Examples:
          // - node_query_node_access_alter()
          $target_entity_type_id = $target_entity_type_id ?: $field_storage_definition->getSetting('target_type');
          $query->addTag("{$target_entity_type_id}_access");
          static::applyAccessConditions($query, $target_entity_type_id, $child_prefix, $cacheability);
          // Keep descending the tree.
          static::secureQuery($query, $target_entity_type_id, $children, $cacheability, $child_prefix);
        }
        elseif (is_null($property_definition)) {
          assert(is_numeric($property_name), 'The specifier is not a property name, it must be a delta.');
          // Keep descending the tree.
          static::secureQuery($query, $entity_type_id, $children, $cacheability, $child_prefix, $field_storage_definition);
        }
      }
    }
  }

  /**
   * Applies access conditions to ensure 'view' access is respected.
   *
   * Since the given entity type might not be the base entity type of the query,
   * the field prefix should be applied to ensure that the conditions are
   * applied to the right subset of entities in the query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query to which access conditions should be applied.
   * @param string $entity_type_id
   *   The entity type for which to access conditions should be applied.
   * @param string|null $field_prefix
   *   A prefix to add before any query condition fields. NULL if no prefix
   *   should be added.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheability
   *   Collects cacheability for the query.
   */
  protected static function applyAccessConditions(QueryInterface $query, $entity_type_id, $field_prefix, CacheableMetadata $cacheability) {
    $access_condition = static::getAccessCondition($entity_type_id, $cacheability);
    if ($access_condition) {
      $prefixed_condition = !is_null($field_prefix)
        ? static::addConditionFieldPrefix($access_condition, $field_prefix)
        : $access_condition;
      $filter = new Filter($prefixed_condition);
      $query->condition($filter->queryCondition($query));
    }
  }

  /**
   * Prefixes all fields in an EntityConditionGroup.
   */
  protected static function addConditionFieldPrefix(EntityConditionGroup $group, $field_prefix) {
    $prefixed = [];
    foreach ($group->members() as $member) {
      if ($member instanceof EntityConditionGroup) {
        $prefixed[] = static::addConditionFieldPrefix($member, $field_prefix);
      }
      else {
        $field = !empty($field_prefix) ? "{$field_prefix}." . $member->field() : $member->field();
        $prefixed[] = new EntityCondition($field, $member->value(), $member->operator());
      }
    }
    return new EntityConditionGroup($group->conjunction(), $prefixed);
  }

  /**
   * Gets an EntityConditionGroup that filters out inaccessible entities.
   *
   * @param string $entity_type_id
   *   The entity type ID for which to get an EntityConditionGroup.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheability
   *   Collects cacheability for the query.
   *
   * @return \Drupal\jsonapi\Query\EntityConditionGroup|null
   *   An EntityConditionGroup or NULL if no conditions need to be applied to
   *   secure an entity query.
   */
  protected static function getAccessCondition($entity_type_id, CacheableMetadata $cacheability) {
    $current_user = \Drupal::currentUser();
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);

    // Get the condition that handles generic restrictions, such as published
    // and owner.
    $generic_condition = static::getAccessConditionForKnownSubsets($entity_type, $current_user, $cacheability);

    // Some entity types require additional conditions. We don't know what
    // contrib entity types require, so they are responsible for implementing
    // hook_query_ENTITY_TYPE_access_alter(). Some core entity types have
    // logic in their access control handler that isn't mirrored in
    // hook_query_ENTITY_TYPE_access_alter(), so we duplicate that here until
    // that's resolved.
    $specific_condition = NULL;
    switch ($entity_type_id) {
      case 'block_content':
        // Allow access only to reusable blocks.
        // @see \Drupal\block_content\BlockContentAccessControlHandler::checkAccess()
        if (isset(static::$fieldManager->getBaseFieldDefinitions($entity_type_id)['reusable'])) {
          $specific_condition = new EntityCondition('reusable', 1);
          $cacheability->addCacheTags($entity_type->getListCacheTags());
        }
        break;

      case 'comment':
        // @see \Drupal\comment\CommentAccessControlHandler::checkAccess()
        $specific_condition = static::getCommentAccessCondition($entity_type, $current_user, $cacheability);
        break;

      case 'entity_test':
        // This case is only necessary for testing comment access controls.
        // @see \Drupal\jsonapi\Tests\Functional\CommentTest::testCollectionFilterAccess()
        $blacklist = \Drupal::state()->get('jsonapi__entity_test_filter_access_blacklist', []);
        $cacheability->addCacheTags(['state:jsonapi__entity_test_filter_access_blacklist']);
        $specific_conditions = [];
        foreach ($blacklist as $id) {
          $specific_conditions[] = new EntityCondition('id', $id, '<>');
        }
        if ($specific_conditions) {
          $specific_condition = new EntityConditionGroup('AND', $specific_conditions);
        }
        break;

      case 'file':
        // Allow access only to public files and files uploaded by the current
        // user.
        // @see \Drupal\file\FileAccessControlHandler::checkAccess()
        $specific_condition = new EntityConditionGroup('OR', [
          new EntityCondition('uri', 'public://', 'STARTS_WITH'),
          new EntityCondition('uid', $current_user->id()),
        ]);
        $cacheability->addCacheTags($entity_type->getListCacheTags());
        break;

      case 'shortcut':
        // Unless the user can administer shortcuts, allow access only to the
        // user's currently displayed shortcut set.
        // @see \Drupal\shortcut\ShortcutAccessControlHandler::checkAccess()
        if (!$current_user->hasPermission('administer shortcuts')) {
          $specific_condition = new EntityCondition('shortcut_set', shortcut_current_displayed_set()->id());
          $cacheability->addCacheContexts(['user']);
          $cacheability->addCacheTags($entity_type->getListCacheTags());
        }
        break;

      case 'user':
        // Disallow querying values of the anonymous user.
        // @see \Drupal\user\UserAccessControlHandler::checkAccess()
        $specific_condition = new EntityCondition('uid', '0', '!=');
        break;
    }

    // Return a combined condition.
    if ($generic_condition && $specific_condition) {
      return new EntityConditionGroup('AND', [$generic_condition, $specific_condition]);
    }
    elseif ($generic_condition) {
      return $generic_condition instanceof EntityConditionGroup ? $generic_condition : new EntityConditionGroup('AND', [$generic_condition]);
    }
    elseif ($specific_condition) {
      return $specific_condition instanceof EntityConditionGroup ? $specific_condition : new EntityConditionGroup('AND', [$specific_condition]);
    }

    return NULL;
  }

  /**
   * Gets an access condition for the allowed JSONAPI_FILTER_AMONG_* subsets.
   *
   * If access is allowed for the JSONAPI_FILTER_AMONG_ALL subset, then no
   * conditions are returned. Otherwise, if access is allowed for
   * JSONAPI_FILTER_AMONG_PUBLISHED, JSONAPI_FILTER_AMONG_ENABLED, or
   * JSONAPI_FILTER_AMONG_OWN, then a condition group is returned for the union
   * of allowed subsets. If no subsets are allowed, then static::alwaysFalse()
   * is returned.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type for which to check filter access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which to check access.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheability
   *   Collects cacheability for the query.
   *
   * @return \Drupal\jsonapi\Query\EntityConditionGroup|null
   *   An EntityConditionGroup or NULL if no conditions need to be applied to
   *   secure an entity query.
   */
  protected static function getAccessConditionForKnownSubsets(EntityTypeInterface $entity_type, AccountInterface $account, CacheableMetadata $cacheability) {
    // Get the combined access results for each JSONAPI_FILTER_AMONG_* subset.
    $access_results = static::getAccessResultsFromEntityFilterHook($entity_type, $account);

    // No conditions are needed if access is allowed for all entities.
    $cacheability->addCacheableDependency($access_results[JSONAPI_FILTER_AMONG_ALL]);
    if ($access_results[JSONAPI_FILTER_AMONG_ALL]->isAllowed()) {
      return NULL;
    }

    // If filtering is not allowed across all entities, but is allowed for
    // certain subsets, then add conditions that reflect those subsets. These
    // will be grouped in an OR to reflect that access may be granted to
    // more than one subset. If no conditions are added below, then
    // static::alwaysFalse() is returned.
    $conditions = [];

    // The "published" subset.
    $published_field_name = $entity_type->getKey('published');
    if ($published_field_name) {
      $access_result = $access_results[JSONAPI_FILTER_AMONG_PUBLISHED];
      $cacheability->addCacheableDependency($access_result);
      if ($access_result->isAllowed()) {
        $conditions[] = new EntityCondition($published_field_name, 1);
        $cacheability->addCacheTags($entity_type->getListCacheTags());
      }
    }

    // The "enabled" subset.
    // @todo Remove ternary when the 'status' key is added to the User entity type.
    $status_field_name = $entity_type->id() === 'user' ? 'status' : $entity_type->getKey('status');
    if ($status_field_name) {
      $access_result = $access_results[JSONAPI_FILTER_AMONG_ENABLED];
      $cacheability->addCacheableDependency($access_result);
      if ($access_result->isAllowed()) {
        $conditions[] = new EntityCondition($status_field_name, 1);
        $cacheability->addCacheTags($entity_type->getListCacheTags());
      }
    }

    // The "owner" subset.
    // @todo Remove ternary when the 'uid' key is added to the User entity type.
    $owner_field_name = $entity_type->id() === 'user' ? 'uid' : $entity_type->getKey('owner');
    if ($owner_field_name) {
      $access_result = $access_results[JSONAPI_FILTER_AMONG_OWN];
      $cacheability->addCacheableDependency($access_result);
      if ($access_result->isAllowed()) {
        $cacheability->addCacheContexts(['user']);
        if ($account->isAuthenticated()) {
          $conditions[] = new EntityCondition($owner_field_name, $account->id());
          $cacheability->addCacheTags($entity_type->getListCacheTags());
        }
      }
    }

    // If no conditions were added above, then access wasn't granted to any
    // subset, so return alwaysFalse().
    if (empty($conditions)) {
      return static::alwaysFalse($entity_type);
    }

    // If more than one condition was added above, then access was granted to
    // more than one subset, so combine them with an OR.
    if (count($conditions) > 1) {
      return new EntityConditionGroup('OR', $conditions);
    }

    // Otherwise return the single condition.
    return $conditions[0];
  }

  /**
   * Gets the combined access result for each JSONAPI_FILTER_AMONG_* subset.
   *
   * This invokes hook_jsonapi_entity_filter_access() and
   * hook_jsonapi_ENTITY_TYPE_filter_access() and combines the results from all
   * of the modules into a single set of results.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type for which to check filter access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface[]
   *   The array of access results, keyed by subset. See
   *   hook_jsonapi_entity_filter_access() for details.
   */
  protected static function getAccessResultsFromEntityFilterHook(EntityTypeInterface $entity_type, AccountInterface $account) {
    /** @var \Drupal\Core\Access\AccessResultInterface[] $combined_access_results */
    $combined_access_results = [
      JSONAPI_FILTER_AMONG_ALL => AccessResult::neutral(),
      JSONAPI_FILTER_AMONG_PUBLISHED => AccessResult::neutral(),
      JSONAPI_FILTER_AMONG_ENABLED => AccessResult::neutral(),
      JSONAPI_FILTER_AMONG_OWN => AccessResult::neutral(),
    ];

    // Invoke hook_jsonapi_entity_filter_access() and
    // hook_jsonapi_ENTITY_TYPE_filter_access() for each module and merge its
    // results with the combined results.
    foreach (['jsonapi_entity_filter_access', 'jsonapi_' . $entity_type->id() . '_filter_access'] as $hook) {
      foreach (static::$moduleHandler->getImplementations($hook) as $module) {
        $module_access_results = static::$moduleHandler->invoke($module, $hook, [$entity_type, $account]);
        if ($module_access_results) {
          foreach ($module_access_results as $subset => $access_result) {
            $combined_access_results[$subset] = $combined_access_results[$subset]->orIf($access_result);
          }
        }
      }
    }

    return $combined_access_results;
  }

  /**
   * Gets an access condition for a comment entity.
   *
   * Unlike all other core entity types, Comment entities' access control
   * depends on access to a referenced entity. More challenging yet, that entity
   * reference field may target different entity types depending on the comment
   * bundle. This makes the query access conditions sufficiently complex to
   * merit a dedicated method.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $comment_entity_type
   *   The comment entity type object.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheability
   *   Collects cacheability for the query.
   * @param int $depth
   *   Internal use only. The recursion depth. It is possible to have comments
   *   on comments, but since comment access is dependent on access to the
   *   entity on which they live, this method can recurse endlessly.
   *
   * @return \Drupal\jsonapi\Query\EntityConditionGroup|null
   *   An EntityConditionGroup or NULL if no conditions need to be applied to
   *   secure an entity query.
   */
  protected static function getCommentAccessCondition(EntityTypeInterface $comment_entity_type, AccountInterface $current_user, CacheableMetadata $cacheability, $depth = 1) {
    // If a comment is assigned to another entity or author the cache needs to
    // be invalidated.
    $cacheability->addCacheTags($comment_entity_type->getListCacheTags());
    // Constructs a big EntityConditionGroup which will filter comments based on
    // the current user's access to the entities on which each comment lives.
    // This is especially complex because comments of different bundles can
    // live on entities of different entity types.
    $comment_entity_type_id = $comment_entity_type->id();
    $field_map = static::$fieldManager->getFieldMapByFieldType('entity_reference');
    assert(isset($field_map[$comment_entity_type_id]['entity_id']['bundles']), 'Every comment has an `entity_id` field.');
    $bundle_ids_by_target_entity_type_id = [];
    foreach ($field_map[$comment_entity_type_id]['entity_id']['bundles'] as $bundle_id) {
      $field_definitions = static::$fieldManager->getFieldDefinitions($comment_entity_type_id, $bundle_id);
      $commented_entity_field_definition = $field_definitions['entity_id'];
      // Each commented entity field definition has a setting which indicates
      // the entity type of the commented entity reference field. This differs
      // per bundle.
      $target_entity_type_id = $commented_entity_field_definition->getSetting('target_type');
      $bundle_ids_by_target_entity_type_id[$target_entity_type_id][] = $bundle_id;
    }
    $bundle_specific_access_conditions = [];
    foreach ($bundle_ids_by_target_entity_type_id as $target_entity_type_id => $bundle_ids) {
      // Construct a field specifier prefix which targets the commented entity.
      $condition_field_prefix = "entity_id.entity:$target_entity_type_id";
      // Ensure that for each possible commented entity type (which varies per
      // bundle), a condition is created that restricts access based on access
      // to the commented entity.
      $bundle_condition = new EntityCondition($comment_entity_type->getKey('bundle'), $bundle_ids, 'IN');
      // Comments on comments can create an infinite recursion! If the target
      // entity type ID is comment, we need special behavior.
      if ($target_entity_type_id === $comment_entity_type_id) {
        $nested_comment_condition = $depth <= 3
          ? static::getCommentAccessCondition($comment_entity_type, $current_user, $cacheability, $depth + 1)
          : static::alwaysFalse($comment_entity_type);
        $prefixed_comment_condition = static::addConditionFieldPrefix($nested_comment_condition, $condition_field_prefix);
        $bundle_specific_access_conditions[$target_entity_type_id] = new EntityConditionGroup('AND', [$bundle_condition, $prefixed_comment_condition]);
      }
      else {
        $target_condition = static::getAccessCondition($target_entity_type_id, $cacheability);
        $bundle_specific_access_conditions[$target_entity_type_id] = !is_null($target_condition)
          ? new EntityConditionGroup('AND', [
            $bundle_condition,
            static::addConditionFieldPrefix($target_condition, $condition_field_prefix),
          ])
          : $bundle_condition;
      }
    }

    // This condition ensures that the user is only permitted to see the
    // comments for which the user is also able to view the entity on which each
    // comment lives.
    $commented_entity_condition = new EntityConditionGroup('OR', array_values($bundle_specific_access_conditions));
    return $commented_entity_condition;
  }

  /**
   * Gets an always FALSE entity condition group for the given entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type for which to construct an impossible condition.
   *
   * @return \Drupal\jsonapi\Query\EntityConditionGroup
   *   An EntityConditionGroup which cannot evaluate to TRUE.
   */
  protected static function alwaysFalse(EntityTypeInterface $entity_type) {
    return new EntityConditionGroup('AND', [
      new EntityCondition($entity_type->getKey('id'), 1, '<'),
      new EntityCondition($entity_type->getKey('id'), 1, '>'),
    ]);
  }

  /**
   * Recursively collects all entity query condition fields.
   *
   * Entity conditions can be nested within AND and OR groups. This recursively
   * finds all unique fields in an entity query condition.
   *
   * @param \Drupal\jsonapi\Query\EntityConditionGroup $group
   *   The root entity condition group.
   * @param array $fields
   *   Internal use only.
   *
   * @return array
   *   An array of entity query condition field names.
   */
  protected static function collectFilteredFields(EntityConditionGroup $group, array $fields = []) {
    foreach ($group->members() as $member) {
      if ($member instanceof EntityConditionGroup) {
        $fields = static::collectFilteredFields($member, $fields);
      }
      else {
        $fields[] = $member->field();
      }
    }
    return array_unique($fields);
  }

  /**
   * Copied from \Drupal\jsonapi\IncludeResolver.
   *
   * @see \Drupal\jsonapi\IncludeResolver::buildTree()
   */
  protected static function buildTree(array $paths) {
    $merged = [];
    foreach ($paths as $parts) {
      // This complex expression is needed to handle the string, "0", which
      // would be evaluated as FALSE.
      if (!is_null(($field_name = array_shift($parts)))) {
        $previous = isset($merged[$field_name]) ? $merged[$field_name] : [];
        $merged[$field_name] = array_merge($previous, [$parts]);
      }
    }
    return !empty($merged) ? array_map([static::class, __FUNCTION__], $merged) : $merged;
  }

}
