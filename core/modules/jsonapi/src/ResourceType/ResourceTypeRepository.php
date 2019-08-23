<?php

namespace Drupal\jsonapi\ResourceType;

use Drupal\Component\Assertion\Inspector;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\ContentEntityNullStorage;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

/**
 * Provides a repository of all JSON:API resource types.
 *
 * Contains the complete set of ResourceType value objects, which are auto-
 * generated based on the Entity Type Manager and Entity Type Bundle Info: one
 * JSON:API resource type per entity type bundle. So, for example:
 * - node--article
 * - node--page
 * - node--…
 * - user--user
 * - …
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/jsonapi/issues/3032787
 * @see jsonapi.api.php
 *
 * @see \Drupal\jsonapi\ResourceType\ResourceType
 */
class ResourceTypeRepository implements ResourceTypeRepositoryInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The bundle manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The static cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $staticCache;

  /**
   * Instance data cache.
   *
   * @var array
   */
  protected $cache = [];

  /**
   * Instantiates a ResourceTypeRepository object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $static_cache
   *   The static cache backend.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_bundle_info, EntityFieldManagerInterface $entity_field_manager, CacheBackendInterface $static_cache) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
    $this->staticCache = $static_cache;
  }

  /**
   * {@inheritdoc}
   */
  public function all() {
    $cached = $this->staticCache->get('jsonapi.resource_types', FALSE);
    if ($cached === FALSE) {
      $resource_types = [];
      foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
        $resource_types = array_merge($resource_types, array_map(function ($bundle) use ($entity_type) {
          return $this->createResourceType($entity_type, (string) $bundle);
        }, array_keys($this->entityTypeBundleInfo->getBundleInfo($entity_type->id()))));
      }
      foreach ($resource_types as $resource_type) {
        $relatable_resource_types = $this->calculateRelatableResourceTypes($resource_type, $resource_types);
        $resource_type->setRelatableResourceTypes($relatable_resource_types);
      }
      $this->staticCache->set('jsonapi.resource_types', $resource_types, Cache::PERMANENT, ['jsonapi_resource_types']);
    }
    return $cached ? $cached->data : $resource_types;
  }

  /**
   * Creates a ResourceType value object for the given entity type + bundle.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to create a JSON:API resource type for.
   * @param string $bundle
   *   The entity type bundle to create a JSON:API resource type for.
   *
   * @return \Drupal\jsonapi\ResourceType\ResourceType
   *   A JSON:API resource type.
   */
  protected function createResourceType(EntityTypeInterface $entity_type, $bundle) {
    $raw_fields = $this->getAllFieldNames($entity_type, $bundle);
    return new ResourceType(
      $entity_type->id(),
      $bundle,
      $entity_type->getClass(),
      $entity_type->isInternal(),
      static::isLocatableResourceType($entity_type, $bundle),
      static::isMutableResourceType($entity_type, $bundle),
      static::isVersionableResourceType($entity_type),
      static::getFieldMapping($raw_fields, $entity_type, $bundle)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function get($entity_type_id, $bundle) {
    assert(is_string($bundle) && !empty($bundle), 'A bundle ID is required. Bundleless entity types should pass the entity type ID again.');
    if (empty($entity_type_id)) {
      throw new PreconditionFailedHttpException('Server error. The current route is malformed.');
    }

    $cid = "jsonapi:resource_type:$entity_type_id:$bundle";
    if (!array_key_exists($cid, $this->cache)) {
      $result = NULL;
      foreach ($this->all() as $resource) {
        if ($resource->getEntityTypeId() == $entity_type_id && $resource->getBundle() == $bundle) {
          $result = $resource;
          break;
        }
      }
      $this->cache[$cid] = $result;
    }

    return $this->cache[$cid];
  }

  /**
   * {@inheritdoc}
   */
  public function getByTypeName($type_name) {
    foreach ($this->all() as $resource) {
      if ($resource->getTypeName() == $type_name) {
        return $resource;
      }
    }
    return NULL;
  }

  /**
   * Gets the field mapping for the given field names and entity type + bundle.
   *
   * @param string[] $field_names
   *   All field names on a bundle of the given entity type.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type for which to get the field mapping.
   * @param string $bundle
   *   The bundle to assess.
   *
   * @return array
   *   An array with:
   *   - keys are (real/internal) field names
   *   - values are either FALSE (indicating the field is not exposed despite
   *     not being internal), TRUE (indicating the field should be exposed under
   *     its internal name) or a string (indicating the field should not be
   *     exposed using its internal name, but the name specified in the string)
   */
  protected static function getFieldMapping(array $field_names, EntityTypeInterface $entity_type, $bundle) {
    assert(Inspector::assertAllStrings($field_names));
    assert($entity_type instanceof ContentEntityTypeInterface || $entity_type instanceof ConfigEntityTypeInterface);
    assert(is_string($bundle) && !empty($bundle), 'A bundle ID is required. Bundleless entity types should pass the entity type ID again.');

    $mapping = [];

    // JSON:API resource identifier objects are sufficient to identify
    // entities. By exposing all fields as attributes, we expose unwanted,
    // confusing or duplicate information:
    // - exposing an entity's ID (which is not a UUID) is bad, but it's
    //   necessary for certain Drupal-coupled clients, so we alias it by
    //   prefixing it with `drupal_internal__`.
    // - exposing an entity's UUID as an attribute is useless (it's already part
    //   of the mandatory "id" attribute in JSON:API), so we disable it in most
    //   cases.
    // - exposing its revision ID as an attribute will compete with any profile
    //   defined meta members used for resource object versioning.
    // @see http://jsonapi.org/format/#document-resource-identifier-objects
    $id_field_name = $entity_type->getKey('id');
    $uuid_field_name = $entity_type->getKey('uuid');
    if ($uuid_field_name !== 'id') {
      $mapping[$uuid_field_name] = FALSE;
    }
    $mapping[$id_field_name] = "drupal_internal__$id_field_name";
    if ($entity_type->isRevisionable() && ($revision_id_field_name = $entity_type->getKey('revision'))) {
      $mapping[$revision_id_field_name] = "drupal_internal__$revision_id_field_name";
    }
    if ($entity_type instanceof ConfigEntityTypeInterface) {
      // The '_core' key is reserved by Drupal core to handle complex edge cases
      // correctly. Data in the '_core' key is irrelevant to clients reading
      // configuration, and is not allowed to be set by clients writing
      // configuration: it is for Drupal core only, and managed by Drupal core.
      // @see https://www.drupal.org/node/2653358
      $mapping['_core'] = FALSE;
    }

    // For all other fields,  use their internal field name also as their public
    // field name.  Unless they're called "id" or "type": those names are
    // reserved by the JSON:API spec.
    // @see http://jsonapi.org/format/#document-resource-object-fields
    foreach (array_diff($field_names, array_keys($mapping)) as $field_name) {
      if ($field_name === 'id' || $field_name === 'type') {
        $alias = $entity_type->id() . '_' . $field_name;
        if (isset($field_name[$alias])) {
          throw new \LogicException('The generated alias conflicts with an existing field. Please report this in the JSON:API issue queue!');
        }
        $mapping[$field_name] = $alias;
        continue;
      }

      // The default, which applies to most fields: expose as-is.
      $mapping[$field_name] = TRUE;
    }

    return $mapping;
  }

  /**
   * Gets all field names for a given entity type and bundle.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type for which to get all field names.
   * @param string $bundle
   *   The bundle for which to get all field names.
   *
   * @return string[]
   *   All field names.
   */
  protected function getAllFieldNames(EntityTypeInterface $entity_type, $bundle) {
    if ($entity_type instanceof ContentEntityTypeInterface) {
      $field_definitions = $this->entityFieldManager->getFieldDefinitions(
        $entity_type->id(),
        $bundle
      );
      return array_keys($field_definitions);
    }
    elseif ($entity_type instanceof ConfigEntityTypeInterface) {
      // @todo Uncomment the first line, remove everything else once https://www.drupal.org/project/drupal/issues/2483407 lands.
      // return array_keys($entity_type->getPropertiesToExport());
      $export_properties = $entity_type->getPropertiesToExport();
      if ($export_properties !== NULL) {
        return array_keys($export_properties);
      }
      else {
        return ['id', 'type', 'uuid', '_core'];
      }
    }
    else {
      throw new \LogicException("Only content and config entity types are supported.");
    }
  }

  /**
   * Whether an entity type + bundle maps to a mutable resource type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to assess.
   * @param string $bundle
   *   The bundle to assess.
   *
   * @return bool
   *   TRUE if the entity type is mutable, FALSE otherwise.
   */
  protected static function isMutableResourceType(EntityTypeInterface $entity_type, $bundle) {
    assert(is_string($bundle) && !empty($bundle), 'A bundle ID is required. Bundleless entity types should pass the entity type ID again.');
    return !$entity_type instanceof ConfigEntityTypeInterface;
  }

  /**
   * Whether an entity type + bundle maps to a locatable resource type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to assess.
   * @param string $bundle
   *   The bundle to assess.
   *
   * @return bool
   *   TRUE if the entity type is locatable, FALSE otherwise.
   */
  protected static function isLocatableResourceType(EntityTypeInterface $entity_type, $bundle) {
    assert(is_string($bundle) && !empty($bundle), 'A bundle ID is required. Bundleless entity types should pass the entity type ID again.');
    return $entity_type->getStorageClass() !== ContentEntityNullStorage::class;
  }

  /**
   * Whether an entity type is a versionable resource type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to assess.
   *
   * @return bool
   *   TRUE if the entity type is versionable, FALSE otherwise.
   */
  protected static function isVersionableResourceType(EntityTypeInterface $entity_type) {
    // @todo: remove the following line and uncomment the next one when revisions have standardized access control. For now, it is unsafe to support all revisionable entity types.
    return in_array($entity_type->id(), ['node', 'media']);
    /* return $entity_type->isRevisionable(); */
  }

  /**
   * Calculates relatable JSON:API resource types for a given resource type.
   *
   * This method has no affect after being called once.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The resource type repository.
   * @param \Drupal\jsonapi\ResourceType\ResourceType[] $resource_types
   *   A list of JSON:API resource types.
   *
   * @return array
   *   The relatable JSON:API resource types, keyed by field name.
   */
  protected function calculateRelatableResourceTypes(ResourceType $resource_type, array $resource_types) {
    // For now, only fieldable entity types may contain relationships.
    $entity_type = $this->entityTypeManager->getDefinition($resource_type->getEntityTypeId());
    if ($entity_type->entityClassImplements(FieldableEntityInterface::class)) {
      $field_definitions = $this->entityFieldManager->getFieldDefinitions(
        $resource_type->getEntityTypeId(),
        $resource_type->getBundle()
      );

      $relatable_internal = array_map(function ($field_definition) use ($resource_types) {
        return $this->getRelatableResourceTypesFromFieldDefinition($field_definition, $resource_types);
      }, array_filter($field_definitions, function ($field_definition) {
        return $this->isReferenceFieldDefinition($field_definition);
      }));

      $relatable_public = [];
      foreach ($relatable_internal as $internal_field_name => $value) {
        $relatable_public[$resource_type->getPublicName($internal_field_name)] = $value;
      }
      return $relatable_public;
    }
    return [];
  }

  /**
   * Get relatable resource types from a field definition.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition from which to calculate relatable JSON:API resource
   *   types.
   * @param \Drupal\jsonapi\ResourceType\ResourceType[] $resource_types
   *   A list of JSON:API resource types.
   *
   * @return \Drupal\jsonapi\ResourceType\ResourceType[]
   *   The JSON:API resource types with which the given field may have a
   *   relationship.
   */
  protected function getRelatableResourceTypesFromFieldDefinition(FieldDefinitionInterface $field_definition, array $resource_types) {
    $item_definition = $field_definition->getItemDefinition();

    $entity_type_id = $item_definition->getSetting('target_type');
    $handler_settings = $item_definition->getSetting('handler_settings');

    $has_target_bundles = isset($handler_settings['target_bundles']) && !empty($handler_settings['target_bundles']);
    $target_bundles = $has_target_bundles ?
      $handler_settings['target_bundles']
      : $this->getAllBundlesForEntityType($entity_type_id);

    return array_map(function ($target_bundle) use ($entity_type_id, $resource_types) {
      foreach ($resource_types as $resource_type) {
        if ($resource_type->getEntityTypeId() === $entity_type_id && $resource_type->getBundle() === $target_bundle) {
          return $resource_type;
        }
      }
    }, $target_bundles);
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
  protected function isReferenceFieldDefinition(FieldDefinitionInterface $field_definition) {
    /* @var \Drupal\Core\Field\TypedData\FieldItemDataDefinition $item_definition */
    $item_definition = $field_definition->getItemDefinition();
    $main_property = $item_definition->getMainPropertyName();
    $property_definition = $item_definition->getPropertyDefinition($main_property);
    return $property_definition instanceof DataReferenceTargetDefinition;
  }

  /**
   * Gets all bundle IDs for a given entity type.
   *
   * @param string $entity_type_id
   *   The entity type for which to get bundles.
   *
   * @return string[]
   *   The bundle IDs.
   */
  protected function getAllBundlesForEntityType($entity_type_id) {
    return array_keys($this->entityTypeBundleInfo->getBundleInfo($entity_type_id));
  }

}
