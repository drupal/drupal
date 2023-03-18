<?php

namespace Drupal\jsonapi\Context;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinitionInterface;
use Drupal\Core\Http\Exception\CacheableAccessDeniedHttpException;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRelationship;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\Core\Http\Exception\CacheableBadRequestHttpException;
use Drupal\Core\Session\AccountInterface;

/**
 * A service that evaluates external path expressions against Drupal fields.
 *
 * This class performs 3 essential functions, path resolution, path validation
 * and path expansion.
 *
 * Path resolution:
 * Path resolution refers to the ability to map a set of external field names to
 * their internal counterparts. This is necessary because a resource type can
 * provide aliases for its field names. For example, the resource type @code
 * node--article @endcode might "alias" the internal field name @code
 * uid @endcode to the external field name @code author @endcode. This permits
 * an API consumer to request @code
 * /jsonapi/node/article?include=author @endcode for a better developer
 * experience.
 *
 * Path validation:
 * Path validation refers to the ability to ensure that a requested path
 * corresponds to a valid set of internal fields. For example, if an API
 * consumer may send a @code GET @endcode request to @code
 * /jsonapi/node/article?sort=author.field_first_name @endcode. The field
 * resolver ensures that @code uid @endcode (which would have been resolved
 * from @code author @endcode) exists on article nodes and that @code
 * field_first_name @endcode exists on user entities. However, in the case of
 * an @code include @endcode path, the field resolver would raise a client error
 * because @code field_first_name @endcode is not an entity reference field,
 * meaning it does not identify any related resources that can be included in a
 * compound document.
 *
 * Path expansion:
 * Path expansion refers to the ability to expand a path to an entity query
 * compatible field expression. For example, a request URL might have a query
 * string like @code ?filter[field_tags.name]=aviation @endcode, before
 * constructing the appropriate entity query, the entity query system needs the
 * path expression to be "expanded" into @code field_tags.entity.name @endcode.
 * In some rare cases, the entity query system needs this to be expanded to
 * @code field_tags.entity:taxonomy_term.name @endcode; the field resolver
 * simply does this by default for every path.
 *
 * *Note:* path expansion is *not* performed for @code include @endcode paths.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class FieldResolver {

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
   * The entity type bundle information service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The JSON:API resource type repository service.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Creates a FieldResolver instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   The field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The bundle info service.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The resource type repository.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user account.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $field_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, ResourceTypeRepositoryInterface $resource_type_repository, ModuleHandlerInterface $module_handler, AccountInterface $current_user) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldManager = $field_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->resourceTypeRepository = $resource_type_repository;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Validates and resolves an include path into its internal possibilities.
   *
   * Each resource type may define its own external names for its internal
   * field names. As a result, a single external include path may target
   * multiple internal paths.
   *
   * This can happen when an entity reference field has different allowed entity
   * types *per bundle* (as is possible with comment entities) or when
   * different resource types share an external field name but resolve to
   * different internal fields names.
   *
   * Example 1:
   * An installation may have three comment types for three different entity
   * types, two of which have a file field and one of which does not. In that
   * case, a path like @code field_comments.entity_id.media @endcode might be
   * resolved to both @code field_comments.entity_id.field_audio @endcode
   * and @code field_comments.entity_id.field_image @endcode.
   *
   * Example 2:
   * A path of @code field_author_profile.account @endcode might
   * resolve to @code field_author_profile.uid @endcode and @code
   * field_author_profile.field_user @endcode if @code
   * field_author_profile @endcode can relate to two different JSON:API resource
   * types (like `node--profile` and `node--migrated_profile`) which have the
   * external field name @code account @endcode aliased to different internal
   * field names.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The resource type for which the path should be validated.
   * @param string[] $path_parts
   *   The include path as an array of strings. For example, the include query
   *   parameter string of @code field_tags.uid @endcode should be given
   *   as @code ['field_tags', 'uid'] @endcode.
   * @param int $depth
   *   (internal) Used to track recursion depth in order to generate better
   *   exception messages.
   *
   * @return string[]
   *   The resolved internal include paths.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown if the path contains invalid specifiers.
   */
  public static function resolveInternalIncludePath(ResourceType $resource_type, array $path_parts, $depth = 0) {
    $cacheability = (new CacheableMetadata())->addCacheContexts(['url.query_args:include']);
    if (empty($path_parts[0])) {
      throw new CacheableBadRequestHttpException($cacheability, 'Empty include path.');
    }
    $public_field_name = $path_parts[0];
    $internal_field_name = $resource_type->getInternalName($public_field_name);
    $relatable_resource_types = $resource_type->getRelatableResourceTypesByField($public_field_name);
    if (empty($relatable_resource_types)) {
      $message = "`$public_field_name` is not a valid relationship field name.";
      if (!empty(($possible = implode(', ', array_keys($resource_type->getRelatableResourceTypes()))))) {
        $message .= " Possible values: $possible.";
      }
      throw new CacheableBadRequestHttpException($cacheability, $message);
    }
    $remaining_parts = array_slice($path_parts, 1);
    if (empty($remaining_parts)) {
      return [[$internal_field_name]];
    }
    $exceptions = [];
    $resolved = [];
    foreach ($relatable_resource_types as $relatable_resource_type) {
      try {
        // Each resource type may resolve the path differently and may return
        // multiple possible resolutions.
        $resolved = array_merge($resolved, static::resolveInternalIncludePath($relatable_resource_type, $remaining_parts, $depth + 1));
      }
      catch (CacheableBadRequestHttpException $e) {
        $exceptions[] = $e;
      }
    }
    if (!empty($exceptions) && count($exceptions) === count($relatable_resource_types)) {
      $previous_messages = implode(' ', array_unique(array_map(function (CacheableBadRequestHttpException $e) {
        return $e->getMessage();
      }, $exceptions)));
      // Only add the full include path on the first level of recursion so that
      // the invalid path phrase isn't repeated at every level.
      throw new CacheableBadRequestHttpException($cacheability, $depth === 0
        ? sprintf("`%s` is not a valid include path. $previous_messages", implode('.', $path_parts))
        : $previous_messages
      );
    }
    // Remove duplicates by converting to strings and then using array_unique().
    $resolved_as_strings = array_map(function ($possibility) {
      return implode('.', $possibility);
    }, $resolved);
    $resolved_as_strings = array_unique($resolved_as_strings);

    // The resolved internal paths do not include the current field name because
    // resolution happens in a recursive process. Convert back from strings.
    return array_map(function ($possibility) use ($internal_field_name) {
      return array_merge([$internal_field_name], explode('.', $possibility));
    }, $resolved_as_strings);
  }

  /**
   * Resolves external field expressions into entity query compatible paths.
   *
   * It is often required to reference data which may exist across a
   * relationship. For example, you may want to sort a list of articles by
   * a field on the article author's representative entity. Or you may wish
   * to filter a list of content by the name of referenced taxonomy terms.
   *
   * In an effort to simplify the referenced paths and align them with the
   * structure of JSON:API responses and the structure of the hypothetical
   * "reference document" (see link), it is possible to alias field names and
   * elide the "entity" keyword from them (this word is used by the entity query
   * system to traverse entity references).
   *
   * This method takes this external field expression and attempts to resolve
   * any aliases and/or abbreviations into a field expression that will be
   * compatible with the entity query system.
   *
   * @link http://jsonapi.org/recommendations/#urls-reference-document
   *
   * Example:
   *   'uid.field_first_name' -> 'uid.entity.field_first_name'.
   *   'author.firstName' -> 'field_author.entity.field_first_name'
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The JSON:API resource type from which to resolve the field name.
   * @param string $external_field_name
   *   The public field name to map to a Drupal field name.
   * @param string $operator
   *   (optional) The operator of the condition for which the path should be
   *   resolved.
   *
   * @return string
   *   The mapped field name.
   *
   * @throws \Drupal\Core\Http\Exception\CacheableBadRequestHttpException
   */
  public function resolveInternalEntityQueryPath(ResourceType $resource_type, $external_field_name, $operator = NULL) {
    $cacheability = (new CacheableMetadata())->addCacheContexts(['url.query_args:filter', 'url.query_args:sort']);
    if (empty($external_field_name)) {
      throw new CacheableBadRequestHttpException($cacheability, 'No field name was provided for the filter.');
    }

    // Turns 'uid.categories.name' into
    // 'uid.entity.field_category.entity.name'. This may be too simple, but it
    // works for the time being.
    $parts = explode('.', $external_field_name);
    $unresolved_path_parts = $parts;
    $reference_breadcrumbs = [];
    /** @var \Drupal\jsonapi\ResourceType\ResourceType[] $resource_types */
    $resource_types = [$resource_type];
    // This complex expression is needed to handle the string, "0", which would
    // otherwise be evaluated as FALSE.
    while (!is_null(($part = array_shift($parts)))) {
      if (!$this->isMemberFilterable($part, $resource_types)) {
        throw new CacheableBadRequestHttpException($cacheability, sprintf(
          'Invalid nested filtering. The field `%s`, given in the path `%s`, does not exist.',
          $part,
          $external_field_name
        ));
      }

      $field_name = $this->getInternalName($part, $resource_types);

      // If none of the resource types are traversable, assume that the
      // remaining path parts are targeting field deltas and/or field
      // properties.
      if (!$this->resourceTypesAreTraversable($resource_types)) {
        $reference_breadcrumbs[] = $field_name;
        return $this->constructInternalPath($reference_breadcrumbs, $parts);
      }

      // Different resource types have different field definitions.
      $candidate_definitions = $this->getFieldItemDefinitions($resource_types, $field_name);
      assert(!empty($candidate_definitions));

      // We have a valid field, so add it to the validated trail of path parts.
      $reference_breadcrumbs[] = $field_name;

      // Remove resource types which do not have a candidate definition.
      $resource_types = array_filter($resource_types, function (ResourceType $resource_type) use ($candidate_definitions) {
        return isset($candidate_definitions[$resource_type->getTypeName()]);
      });

      // Check access to execute a query for each field per resource type since
      // field definitions are bundle-specific.
      foreach ($resource_types as $resource_type) {
        $field_access = $this->getFieldAccess($resource_type, $field_name);
        $cacheability->addCacheableDependency($field_access);
        if (!$field_access->isAllowed()) {
          $message = sprintf('The current user is not authorized to filter by the `%s` field, given in the path `%s`.', $field_name, implode('.', $reference_breadcrumbs));
          if ($field_access instanceof AccessResultReasonInterface && ($reason = $field_access->getReason()) && !empty($reason)) {
            $message .= ' ' . $reason;
          }
          throw new CacheableAccessDeniedHttpException($cacheability, $message);
        }
      }

      // Get all of the referenceable resource types.
      $resource_types = $this->getRelatableResourceTypes($resource_types, $candidate_definitions);

      $at_least_one_entity_reference_field = FALSE;
      $candidate_property_names = array_unique(NestedArray::mergeDeepArray(array_map(function (FieldItemDataDefinitionInterface $definition) use (&$at_least_one_entity_reference_field) {
        $property_definitions = $definition->getPropertyDefinitions();
        return array_reduce(array_keys($property_definitions), function ($property_names, $property_name) use ($property_definitions, &$at_least_one_entity_reference_field) {
          $property_definition = $property_definitions[$property_name];
          $is_data_reference_definition = $property_definition instanceof DataReferenceTargetDefinition;
          if (!$property_definition->isInternal()) {
            // Entity reference fields are special: their reference property
            // (usually `target_id`) is exposed in the JSON:API representation
            // with a prefix.
            $property_names[] = $is_data_reference_definition ? 'id' : $property_name;
          }
          if ($is_data_reference_definition) {
            $at_least_one_entity_reference_field = TRUE;
            $property_names[] = "drupal_internal__$property_name";
          }
          return $property_names;
        }, []);
      }, $candidate_definitions)));

      // Determine if the specified field has one property or many in its
      // JSON:API representation, or if it is an relationship (an entity
      // reference field), in which case the `id` of the related resource must
      // always be specified.
      $property_specifier_needed = $at_least_one_entity_reference_field || count($candidate_property_names) > 1;

      // If there are no remaining path parts, the process is finished unless
      // the field has multiple properties, in which case one must be specified.
      if (empty($parts)) {
        // If the operator is asserting the presence or absence of a
        // relationship entirely, it does not make sense to require a property
        // specifier.
        if ($property_specifier_needed && (!$at_least_one_entity_reference_field || !in_array($operator, ['IS NULL', 'IS NOT NULL'], TRUE))) {
          $possible_specifiers = array_map(function ($specifier) use ($at_least_one_entity_reference_field) {
            return $at_least_one_entity_reference_field && $specifier !== 'id' ? "meta.$specifier" : $specifier;
          }, $candidate_property_names);
          throw new CacheableBadRequestHttpException($cacheability, sprintf('Invalid nested filtering. The field `%s`, given in the path `%s` is incomplete, it must end with one of the following specifiers: `%s`.', $part, $external_field_name, implode('`, `', $possible_specifiers)));
        }
        return $this->constructInternalPath($reference_breadcrumbs);
      }

      // If the next part is a delta, as in "body.0.value", then we add it to
      // the breadcrumbs and remove it from the parts that still must be
      // processed.
      if (static::isDelta($parts[0])) {
        $reference_breadcrumbs[] = array_shift($parts);
      }

      // If there are no remaining path parts, the process is finished.
      if (empty($parts)) {
        return $this->constructInternalPath($reference_breadcrumbs);
      }

      // JSON:API outputs entity reference field properties under a meta object
      // on a relationship. If the filter specifies one of these properties, it
      // must prefix the property name with `meta`. The only exception is if the
      // next path part is the same as the name for the reference property
      // (typically `entity`), this is permitted to disambiguate the case of a
      // field name on the target entity which is the same a property name on
      // the entity reference field.
      if ($at_least_one_entity_reference_field && $parts[0] !== 'id') {
        if ($parts[0] === 'meta') {
          array_shift($parts);
        }
        elseif (in_array($parts[0], $candidate_property_names) && !static::isCandidateDefinitionReferenceProperty($parts[0], $candidate_definitions)) {
          throw new CacheableBadRequestHttpException($cacheability, sprintf('Invalid nested filtering. The property `%s`, given in the path `%s` belongs to the meta object of a relationship and must be preceded by `meta`.', $parts[0], $external_field_name));
        }
      }

      // Determine if the next part is not a property of $field_name.
      if (!static::isCandidateDefinitionProperty($parts[0], $candidate_definitions) && !empty(static::getAllDataReferencePropertyNames($candidate_definitions))) {
        // The next path part is neither a delta nor a field property, so it
        // must be a field on a targeted resource type. We need to guess the
        // intermediate reference property since one was not provided.
        //
        // For example, the path `uid.name` for a `node--article` resource type
        // will be resolved into `uid.entity.name`.
        $reference_breadcrumbs[] = static::getDataReferencePropertyName($candidate_definitions, $parts, $unresolved_path_parts);
      }
      else {
        // If the property is not a reference property, then all
        // remaining parts must be further property specifiers.
        if (!static::isCandidateDefinitionReferenceProperty($parts[0], $candidate_definitions)) {
          // If a field property is specified on a field with only one property
          // defined, throw an error because in the JSON:API output, it does not
          // exist. This is because JSON:API elides single-value properties;
          // respecting it would leak this Drupalism out.
          if (count($candidate_property_names) === 1) {
            throw new CacheableBadRequestHttpException($cacheability, sprintf('Invalid nested filtering. The property `%s`, given in the path `%s`, does not exist. Filter by `%s`, not `%s` (the JSON:API module elides property names from single-property fields).', $parts[0], $external_field_name, substr($external_field_name, 0, strlen($external_field_name) - strlen($parts[0]) - 1), $external_field_name));
          }
          elseif (!in_array($parts[0], $candidate_property_names, TRUE)) {
            throw new CacheableBadRequestHttpException($cacheability, sprintf('Invalid nested filtering. The property `%s`, given in the path `%s`, does not exist. Must be one of the following property names: `%s`.', $parts[0], $external_field_name, implode('`, `', $candidate_property_names)));
          }
          return $this->constructInternalPath($reference_breadcrumbs, $parts);
        }
        // The property is a reference, so add it to the breadcrumbs and
        // continue resolving fields.
        $reference_breadcrumbs[] = array_shift($parts);
      }
    }

    // Reconstruct the full path to the final reference field.
    return $this->constructInternalPath($reference_breadcrumbs);
  }

  /**
   * Expands the internal path with the "entity" keyword.
   *
   * @param string[] $references
   *   The resolved internal field names of all entity references.
   * @param string[] $property_path
   *   (optional) A sub-property path for the last field in the path.
   *
   * @return string
   *   The expanded and imploded path.
   */
  protected function constructInternalPath(array $references, array $property_path = []) {
    // Reconstruct the path parts that are referencing sub-properties.
    $field_path = implode('.', array_map(function ($part) {
      return str_replace('drupal_internal__', '', $part);
    }, $property_path));

    // This rebuilds the path from the real, internal field names that have
    // been traversed so far. It joins them with the "entity" keyword as
    // required by the entity query system.
    $entity_path = implode('.', $references);

    // Reconstruct the full path to the final reference field.
    return (empty($field_path)) ? $entity_path : $entity_path . '.' . $field_path;
  }

  /**
   * Get all item definitions from a set of resources types by a field name.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType[] $resource_types
   *   The resource types on which the field might exist.
   * @param string $field_name
   *   The field for which to retrieve field item definitions.
   *
   * @return \Drupal\Core\TypedData\ComplexDataDefinitionInterface[]
   *   The found field item definitions.
   */
  protected function getFieldItemDefinitions(array $resource_types, $field_name) {
    return array_reduce($resource_types, function ($result, ResourceType $resource_type) use ($field_name) {
      /** @var \Drupal\jsonapi\ResourceType\ResourceType $resource_type */
      $entity_type = $resource_type->getEntityTypeId();
      $bundle = $resource_type->getBundle();
      $definitions = $this->fieldManager->getFieldDefinitions($entity_type, $bundle);
      if (isset($definitions[$field_name])) {
        $result[$resource_type->getTypeName()] = $definitions[$field_name]->getItemDefinition();
      }
      return $result;
    }, []);
  }

  /**
   * Resolves the UUID field name for a resource type.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The resource type for which to get the UUID field name.
   *
   * @return string
   *   The resolved internal name.
   */
  protected function getIdFieldName(ResourceType $resource_type) {
    $entity_type = $this->entityTypeManager->getDefinition($resource_type->getEntityTypeId());
    return $entity_type->getKey('uuid');
  }

  /**
   * Resolves the internal field name based on a collection of resource types.
   *
   * @param string $field_name
   *   The external field name.
   * @param \Drupal\jsonapi\ResourceType\ResourceType[] $resource_types
   *   The resource types from which to get an internal name.
   *
   * @return string
   *   The resolved internal name.
   */
  protected function getInternalName($field_name, array $resource_types) {
    return array_reduce($resource_types, function ($carry, ResourceType $resource_type) use ($field_name) {
      if ($carry != $field_name) {
        // We already found the internal name.
        return $carry;
      }
      return $field_name === 'id' ? $this->getIdFieldName($resource_type) : $resource_type->getInternalName($field_name);
    }, $field_name);
  }

  /**
   * Determines if the given field or member name is filterable.
   *
   * @param string $external_name
   *   The external field or member name.
   * @param \Drupal\jsonapi\ResourceType\ResourceType[] $resource_types
   *   The resource types to test.
   *
   * @return bool
   *   Whether the given field is present as a filterable member of the targeted
   *   resource objects.
   */
  protected function isMemberFilterable($external_name, array $resource_types) {
    return array_reduce($resource_types, function ($carry, ResourceType $resource_type) use ($external_name) {
      // @todo: remove the next line and uncomment the following one in https://www.drupal.org/project/drupal/issues/3017047.
      return $carry ?: $external_name === 'id' || $resource_type->isFieldEnabled($resource_type->getInternalName($external_name));
      /*return $carry ?: in_array($external_name, ['id', 'type']) || $resource_type->isFieldEnabled($resource_type->getInternalName($external_name));*/
    }, FALSE);
  }

  /**
   * Get the referenceable ResourceTypes for a set of field definitions.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType[] $resource_types
   *   The resource types on which the reference field might exist.
   * @param \Drupal\Core\Field\TypedData\FieldItemDataDefinitionInterface[] $definitions
   *   The field item definitions of targeted fields, keyed by the resource
   *   type name on which they reside.
   *
   * @return \Drupal\jsonapi\ResourceType\ResourceType[]
   *   The referenceable target resource types.
   */
  protected function getRelatableResourceTypes(array $resource_types, array $definitions) {
    $relatable_resource_types = [];
    foreach ($resource_types as $resource_type) {
      $definition = $definitions[$resource_type->getTypeName()];
      $resource_type_field = $resource_type->getFieldByInternalName($definition->getFieldDefinition()->getName());
      if ($resource_type_field instanceof ResourceTypeRelationship) {
        foreach ($resource_type_field->getRelatableResourceTypes() as $relatable_resource_type) {
          $relatable_resource_types[$relatable_resource_type->getTypeName()] = $relatable_resource_type;
        }
      }
    }
    return $relatable_resource_types;
  }

  /**
   * Whether the given resources can be traversed to other resources.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType[] $resource_types
   *   The resources types to evaluate.
   *
   * @return bool
   *   TRUE if any one of the given resource types is traversable.
   *
   * @todo This class shouldn't be aware of entity types and their definitions.
   * Whether a resource can have relationships to other resources is information
   * we ought to be able to discover on the ResourceType. However, we cannot
   * reliably determine this information with existing APIs. Entities may be
   * backed by various storages that are unable to perform queries across
   * references and certain storages may not be able to store references at all.
   */
  protected function resourceTypesAreTraversable(array $resource_types) {
    foreach ($resource_types as $resource_type) {
      $entity_type_definition = $this->entityTypeManager->getDefinition($resource_type->getEntityTypeId());
      if ($entity_type_definition->entityClassImplements(FieldableEntityInterface::class)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Gets all unique reference property names from the given field definitions.
   *
   * @param \Drupal\Core\TypedData\ComplexDataDefinitionInterface[] $candidate_definitions
   *   A list of targeted field item definitions specified by the path.
   *
   * @return string[]
   *   The reference property names, if any.
   */
  protected static function getAllDataReferencePropertyNames(array $candidate_definitions) {
    $reference_property_names = array_reduce($candidate_definitions, function (array $reference_property_names, ComplexDataDefinitionInterface $definition) {
      $property_definitions = $definition->getPropertyDefinitions();
      foreach ($property_definitions as $property_name => $property_definition) {
        if ($property_definition instanceof DataReferenceDefinitionInterface) {
          $target_definition = $property_definition->getTargetDefinition();
          assert($target_definition instanceof EntityDataDefinitionInterface, 'Entity reference fields should only be able to reference entities.');
          $reference_property_names[] = $property_name . ':' . $target_definition->getEntityTypeId();
        }
      }
      return $reference_property_names;
    }, []);
    return array_unique($reference_property_names);
  }

  /**
   * Determines the reference property name for the remaining unresolved parts.
   *
   * @param \Drupal\Core\TypedData\ComplexDataDefinitionInterface[] $candidate_definitions
   *   A list of targeted field item definitions specified by the path.
   * @param string[] $remaining_parts
   *   The remaining path parts.
   * @param string[] $unresolved_path_parts
   *   The unresolved path parts.
   *
   * @return string
   *   The reference name.
   */
  protected static function getDataReferencePropertyName(array $candidate_definitions, array $remaining_parts, array $unresolved_path_parts) {
    $unique_reference_names = static::getAllDataReferencePropertyNames($candidate_definitions);
    if (count($unique_reference_names) > 1) {
      $choices = array_map(function ($reference_name) use ($unresolved_path_parts, $remaining_parts) {
        $prior_parts = array_slice($unresolved_path_parts, 0, count($unresolved_path_parts) - count($remaining_parts));
        return implode('.', array_merge($prior_parts, [$reference_name], $remaining_parts));
      }, $unique_reference_names);
      // @todo Add test coverage for this in https://www.drupal.org/project/drupal/issues/2971281
      $message = sprintf('Ambiguous path. Try one of the following: %s, in place of the given path: %s', implode(', ', $choices), implode('.', $unresolved_path_parts));
      $cacheability = (new CacheableMetadata())->addCacheContexts(['url.query_args:filter', 'url.query_args:sort']);
      throw new CacheableBadRequestHttpException($cacheability, $message);
    }
    return $unique_reference_names[0];
  }

  /**
   * Determines if a path part targets a specific field delta.
   *
   * @param string $part
   *   The path part.
   *
   * @return bool
   *   TRUE if the part is an integer, FALSE otherwise.
   */
  protected static function isDelta($part) {
    return (bool) preg_match('/^[0-9]+$/', $part);
  }

  /**
   * Determines if a path part targets a field property, not a subsequent field.
   *
   * @param string $part
   *   The path part.
   * @param \Drupal\Core\TypedData\ComplexDataDefinitionInterface[] $candidate_definitions
   *   A list of targeted field item definitions which are specified by the
   *   path.
   *
   * @return bool
   *   TRUE if the part is a property of one of the candidate definitions, FALSE
   *   otherwise.
   */
  protected static function isCandidateDefinitionProperty($part, array $candidate_definitions) {
    $part = static::getPathPartPropertyName($part);
    foreach ($candidate_definitions as $definition) {
      $property_definitions = $definition->getPropertyDefinitions();

      foreach ($property_definitions as $property_name => $property_definition) {
        $property_name = $property_definition instanceof DataReferenceTargetDefinition
          ? "drupal_internal__$property_name"
          : $property_name;
        if ($part === $property_name) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Determines if a path part targets a reference property.
   *
   * @param string $part
   *   The path part.
   * @param \Drupal\Core\TypedData\ComplexDataDefinitionInterface[] $candidate_definitions
   *   A list of targeted field item definitions which are specified by the
   *   path.
   *
   * @return bool
   *   TRUE if the part is a property of one of the candidate definitions, FALSE
   *   otherwise.
   */
  protected static function isCandidateDefinitionReferenceProperty($part, array $candidate_definitions) {
    $part = static::getPathPartPropertyName($part);
    foreach ($candidate_definitions as $definition) {
      $property = $definition->getPropertyDefinition($part);
      if ($property && $property instanceof DataReferenceDefinitionInterface) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Gets the property name from an entity typed or untyped path part.
   *
   * A path part may contain an entity type specifier like `entity:node`. This
   * extracts the actual property name. If an entity type is not specified, then
   * the path part is simply returned. For example, both `foo` and `foo:bar`
   * will return `foo`.
   *
   * @param string $part
   *   A path part.
   *
   * @return string
   *   The property name from a path part.
   */
  protected static function getPathPartPropertyName($part) {
    return str_contains($part, ':') ? explode(':', $part)[0] : $part;
  }

  /**
   * Gets the field access result for the 'view' operation.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The JSON:API resource type on which the field exists.
   * @param string $internal_field_name
   *   The field name for which access should be checked.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The 'view' access result.
   */
  protected function getFieldAccess(ResourceType $resource_type, $internal_field_name) {
    $definitions = $this->fieldManager->getFieldDefinitions($resource_type->getEntityTypeId(), $resource_type->getBundle());
    assert(isset($definitions[$internal_field_name]), 'The field name should have already been validated.');
    $field_definition = $definitions[$internal_field_name];
    $filter_access_results = $this->moduleHandler->invokeAll('jsonapi_entity_field_filter_access', [$field_definition, $this->currentUser]);
    $filter_access_result = array_reduce($filter_access_results, function (AccessResultInterface $combined_result, AccessResultInterface $result) {
      return $combined_result->orIf($result);
    }, AccessResult::neutral());
    if (!$filter_access_result->isNeutral()) {
      return $filter_access_result;
    }
    $entity_access_control_handler = $this->entityTypeManager->getAccessControlHandler($resource_type->getEntityTypeId());
    $field_access = $entity_access_control_handler->fieldAccess('view', $field_definition, NULL, NULL, TRUE);
    return $filter_access_result->orIf($field_access);
  }

}
