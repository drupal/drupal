<?php

namespace Drupal\jsonapi;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\jsonapi\Access\EntityAccessChecker;
use Drupal\jsonapi\Context\FieldResolver;
use Drupal\jsonapi\Exception\EntityAccessDeniedHttpException;
use Drupal\jsonapi\JsonApiResource\Data;
use Drupal\jsonapi\JsonApiResource\IncludedData;
use Drupal\jsonapi\JsonApiResource\LabelOnlyResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceIdentifierInterface;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\ResourceType\ResourceType;

/**
 * Resolves included resources for an entity or collection of entities.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/jsonapi/issues/3032787
 * @see jsonapi.api.php
 */
class IncludeResolver {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The JSON:API entity access checker.
   *
   * @var \Drupal\jsonapi\Access\EntityAccessChecker
   */
  protected $entityAccessChecker;

  /**
   * IncludeResolver constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityAccessChecker $entity_access_checker) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityAccessChecker = $entity_access_checker;
  }

  /**
   * Resolves included resources.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceIdentifierInterface|\Drupal\jsonapi\JsonApiResource\ResourceObjectData $data
   *   The resource(s) for which to resolve includes.
   * @param string $include_parameter
   *   The include query parameter to resolve.
   *
   * @return \Drupal\jsonapi\JsonApiResource\IncludedData
   *   An IncludedData object of resolved resources to be included.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if an included entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if a storage handler couldn't be loaded.
   */
  public function resolve($data, $include_parameter) {
    assert($data instanceof ResourceObject || $data instanceof ResourceObjectData);
    $data = $data instanceof ResourceObjectData ? $data : new ResourceObjectData([$data], 1);
    $include_tree = static::toIncludeTree($data, $include_parameter);
    return IncludedData::deduplicate($this->resolveIncludeTree($include_tree, $data));
  }

  /**
   * Receives a tree of include field names and resolves resources for it.
   *
   * This method takes a tree of relationship field names and JSON:API Data
   * object. For the top-level of the tree and for each entity in the
   * collection, it gets the target entity type and IDs for each relationship
   * field. The method then loads all of those targets and calls itself
   * recursively with the next level of the tree and those loaded resources.
   *
   * @param array $include_tree
   *   The include paths, represented as a tree.
   * @param \Drupal\jsonapi\JsonApiResource\Data $data
   *   The entity collection from which includes should be resolved.
   * @param \Drupal\jsonapi\JsonApiResource\Data|null $includes
   *   (Internal use only) Any prior resolved includes.
   *
   * @return \Drupal\jsonapi\JsonApiResource\Data
   *   A JSON:API Data of included items.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if an included entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if a storage handler couldn't be loaded.
   */
  protected function resolveIncludeTree(array $include_tree, Data $data, Data $includes = NULL) {
    $includes = is_null($includes) ? new IncludedData([]) : $includes;
    foreach ($include_tree as $field_name => $children) {
      $references = [];
      foreach ($data as $resource_object) {
        // Some objects in the collection may be LabelOnlyResourceObjects or
        // EntityAccessDeniedHttpException objects.
        assert($resource_object instanceof ResourceIdentifierInterface);
        if ($resource_object instanceof LabelOnlyResourceObject) {
          $message = "The current user is not allowed to view this relationship.";
          $exception = new EntityAccessDeniedHttpException($resource_object->getEntity(), AccessResult::forbidden("The user only has authorization for the 'view label' operation."), '', $message, $field_name);
          $includes = IncludedData::merge($includes, new IncludedData([$exception]));
          continue;
        }
        elseif (!$resource_object instanceof ResourceObject) {
          continue;
        }
        $public_field_name = $resource_object->getResourceType()->getPublicName($field_name);
        // Not all entities in $entity_collection will be of the same bundle and
        // may not have all of the same fields. Therefore, calling
        // $resource_object->get($a_missing_field_name) will result in an
        // exception.
        if (!$resource_object->hasField($public_field_name)) {
          continue;
        }
        $field_list = $resource_object->getField($public_field_name);
        // Config entities don't have real fields and can't have relationships.
        if (!$field_list instanceof FieldItemListInterface) {
          continue;
        }
        $field_access = $field_list->access('view', NULL, TRUE);
        if (!$field_access->isAllowed()) {
          $message = 'The current user is not allowed to view this relationship.';
          $exception = new EntityAccessDeniedHttpException($field_list->getEntity(), $field_access, '', $message, $public_field_name);
          $includes = IncludedData::merge($includes, new IncludedData([$exception]));
          continue;
        }
        $target_type = $field_list->getFieldDefinition()->getFieldStorageDefinition()->getSetting('target_type');
        assert(!empty($target_type));
        foreach ($field_list as $field_item) {
          assert($field_item instanceof EntityReferenceItem);
          $references[$target_type][] = $field_item->get($field_item::mainPropertyName())->getValue();
        }
      }
      foreach ($references as $target_type => $ids) {
        $entity_storage = $this->entityTypeManager->getStorage($target_type);
        $targeted_entities = $entity_storage->loadMultiple(array_unique($ids));
        $access_checked_entities = array_map(function (EntityInterface $entity) {
          return $this->entityAccessChecker->getAccessCheckedResourceObject($entity);
        }, $targeted_entities);
        $targeted_collection = new IncludedData(array_filter($access_checked_entities, function (ResourceIdentifierInterface $resource_object) {
          return !$resource_object->getResourceType()->isInternal();
        }));
        $includes = static::resolveIncludeTree($children, $targeted_collection, IncludedData::merge($includes, $targeted_collection));
      }
    }
    return $includes;
  }

  /**
   * Returns a tree of field names to include from an include parameter.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObjectData $data
   *   The base resources for which includes should be resolved.
   * @param string $include_parameter
   *   The raw include parameter value.
   *
   * @return array
   *   An multi-dimensional array representing a tree of field names to be
   *   included. Array keys are the field names. Leaves are empty arrays.
   */
  protected static function toIncludeTree(ResourceObjectData $data, $include_parameter) {
    // $include_parameter: 'one.two.three, one.two.four'.
    $include_paths = array_map('trim', explode(',', $include_parameter));
    // $exploded_paths: [['one', 'two', 'three'], ['one', 'two', 'four']].
    $exploded_paths = array_map(function ($include_path) {
      return array_map('trim', explode('.', $include_path));
    }, $include_paths);
    $resolved_paths = [];
    /* @var \Drupal\jsonapi\JsonApiResource\ResourceIdentifierInterface $resource_object */
    foreach ($data as $resource_object) {
      $resolved_paths = array_merge($resolved_paths, static::resolveInternalIncludePaths($resource_object->getResourceType(), $exploded_paths));
    }
    return static::buildTree($resolved_paths);
  }

  /**
   * Resolves an array of public field paths.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $base_resource_type
   *   The base resource type from which to resolve an internal include path.
   * @param array $paths
   *   An array of exploded include paths.
   *
   * @return array
   *   An array of all possible internal include paths derived from the given
   *   public include paths.
   *
   * @see self::buildTree
   */
  protected static function resolveInternalIncludePaths(ResourceType $base_resource_type, array $paths) {
    $internal_paths = array_map(function ($exploded_path) use ($base_resource_type) {
      if (empty($exploded_path)) {
        return [];
      }
      return FieldResolver::resolveInternalIncludePath($base_resource_type, $exploded_path);
    }, $paths);
    $flattened_paths = array_reduce($internal_paths, 'array_merge', []);
    return $flattened_paths;
  }

  /**
   * Takes an array of exploded paths and builds a tree of field names.
   *
   * Input example: [
   *   ['one', 'two', 'three'],
   *   ['one', 'two', 'four'],
   *   ['one', 'two', 'internal'],
   * ]
   *
   * Output example: [
   *   'one' => [
   *     'two' [
   *       'three' => [],
   *       'four' => [],
   *       'internal' => [],
   *     ],
   *   ],
   * ]
   *
   * @param array $paths
   *   An array of exploded include paths.
   *
   * @return array
   *   An multi-dimensional array representing a tree of field names to be
   *   included. Array keys are the field names. Leaves are empty arrays.
   */
  protected static function buildTree(array $paths) {
    $merged = [];
    foreach ($paths as $parts) {
      if (!$field_name = array_shift($parts)) {
        continue;
      }
      $previous = isset($merged[$field_name]) ? $merged[$field_name] : [];
      $merged[$field_name] = array_merge($previous, [$parts]);
    }
    return !empty($merged) ? array_map([static::class, __FUNCTION__], $merged) : $merged;
  }

}
