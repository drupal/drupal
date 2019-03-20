<?php

namespace Drupal\jsonapi\ResourceType;

/**
 * Value object containing all metadata for a JSON:API resource type.
 *
 * Used to generate routes (collection, individual, etcetera), generate
 * relationship links, and so on.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/jsonapi/issues/3032787
 * @see jsonapi.api.php
 *
 * @see \Drupal\jsonapi\ResourceType\ResourceTypeRepository
 */
class ResourceType {

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The bundle ID.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The type name.
   *
   * @var string
   */
  protected $typeName;

  /**
   * The class to which a payload converts to.
   *
   * @var string
   */
  protected $deserializationTargetClass;

  /**
   * Whether this resource type is internal.
   *
   * @var bool
   */
  protected $internal;

  /**
   * Whether this resource type's resources are locatable.
   *
   * @var bool
   */
  protected $isLocatable;

  /**
   * Whether this resource type's resources are mutable.
   *
   * @var bool
   */
  protected $isMutable;

  /**
   * Whether this resource type's resources are versionable.
   *
   * @var bool
   */
  protected $isVersionable;

  /**
   * The list of fields on the underlying entity type + bundle.
   *
   * @var string[]
   */
  protected $fields;

  /**
   * The list of disabled fields. Disabled by default: uuid, id, type.
   *
   * @var string[]
   *
   * @see \Drupal\jsonapi\ResourceType\ResourceTypeRepository::getFieldMapping()
   */
  protected $disabledFields;

  /**
   * The mapping for field aliases: keys=internal names, values=public names.
   *
   * @var string[]
   */
  protected $fieldMapping;

  /**
   * The inverse of $fieldMapping.
   *
   * @var string[]
   */
  protected $invertedFieldMapping;

  /**
   * Gets the entity type ID.
   *
   * @return string
   *   The entity type ID.
   *
   * @see \Drupal\Core\Entity\EntityInterface::getEntityTypeId
   */
  public function getEntityTypeId() {
    return $this->entityTypeId;
  }

  /**
   * Gets the type name.
   *
   * @return string
   *   The type name.
   */
  public function getTypeName() {
    return $this->typeName;
  }

  /**
   * Gets the bundle.
   *
   * @return string
   *   The bundle of the entity. Defaults to the entity type ID if the entity
   *   type does not make use of different bundles.
   *
   * @see \Drupal\Core\Entity\EntityInterface::bundle
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * Gets the deserialization target class.
   *
   * @return string
   *   The deserialization target class.
   */
  public function getDeserializationTargetClass() {
    return $this->deserializationTargetClass;
  }

  /**
   * Translates the entity field name to the public field name.
   *
   * This is only here so we can allow polymorphic implementations to take a
   * greater control on the field names.
   *
   * @return string
   *   The public field name.
   */
  public function getPublicName($field_name) {
    // By default the entity field name is the public field name.
    return isset($this->fieldMapping[$field_name])
      ? $this->fieldMapping[$field_name]
      : $field_name;
  }

  /**
   * Translates the public field name to the entity field name.
   *
   * This is only here so we can allow polymorphic implementations to take a
   * greater control on the field names.
   *
   * @return string
   *   The internal field name as defined in the entity.
   */
  public function getInternalName($field_name) {
    // By default the entity field name is the public field name.
    return isset($this->invertedFieldMapping[$field_name])
      ? $this->invertedFieldMapping[$field_name]
      : $field_name;
  }

  /**
   * Checks if the field exists.
   *
   * Note: a minority of config entity types which do not define a
   * `config_export` in their entity type annotation will not have their fields
   * represented here because it is impossible to determine them without an
   * instance of config available.
   *
   * @todo Refactor this in Drupal 9, because thanks to https://www.drupal.org/project/drupal/issues/2949021, `config_export` will be guaranteed to exist, and this won't need an instance anymore.
   *
   * @param string $field_name
   *   The internal field name.
   *
   * @return bool
   *   TRUE if the field is known to exist on the resource type; FALSE
   *   otherwise.
   */
  public function hasField($field_name) {
    return in_array($field_name, $this->fields, TRUE);
  }

  /**
   * Checks if a field is enabled or not.
   *
   * This is only here so we can allow polymorphic implementations to take a
   * greater control on the data model.
   *
   * @param string $field_name
   *   The internal field name.
   *
   * @return bool
   *   TRUE if the field exists and is enabled and should be considered as part
   *   of the data model. FALSE otherwise.
   */
  public function isFieldEnabled($field_name) {
    return $this->hasField($field_name) && !in_array($field_name, $this->disabledFields, TRUE);
  }

  /**
   * Determine whether to include a collection count.
   *
   * @return bool
   *   Whether to include a collection count.
   */
  public function includeCount() {
    // By default, do not return counts in collection queries.
    return FALSE;
  }

  /**
   * Whether this resource type is internal.
   *
   * This must not be used as an access control mechanism.
   *
   * Internal resource types are not available via the HTTP API. They have no
   * routes and cannot be used for filtering or sorting. They cannot be included
   * in the response using the `include` query parameter.
   *
   * However, relationship fields on public resources *will include* a resource
   * identifier for the referenced internal resource.
   *
   * This method exists to remove data that should not logically be exposed by
   * the HTTP API. For example, read-only data from an internal resource might
   * be embedded in a public resource using computed fields. Therefore,
   * including the internal resource as a relationship with distinct routes
   * might uneccesarilly expose internal implementation details.
   *
   * @return bool
   *   TRUE if the resource type is internal. FALSE otherwise.
   */
  public function isInternal() {
    return $this->internal;
  }

  /**
   * Whether resources of this resource type are locatable.
   *
   * A resource type may for example not be locatable when it is not stored.
   *
   * @return bool
   *   TRUE if the resource type's resources are locatable. FALSE otherwise.
   */
  public function isLocatable() {
    return $this->isLocatable;
  }

  /**
   * Whether resources of this resource type are mutable.
   *
   * Indicates that resources of this type may not be created, updated or
   * deleted (POST, PATCH or DELETE, respectively).
   *
   * @return bool
   *   TRUE if the resource type's resources are mutable. FALSE otherwise.
   */
  public function isMutable() {
    return $this->isMutable;
  }

  /**
   * Whether resources of this resource type are versionable.
   *
   * @return bool
   *   TRUE if the resource type's resources are versionable. FALSE otherwise.
   */
  public function isVersionable() {
    return $this->isVersionable;
  }

  /**
   * Instantiates a ResourceType object.
   *
   * @param string $entity_type_id
   *   An entity type ID.
   * @param string $bundle
   *   A bundle.
   * @param string $deserialization_target_class
   *   The deserialization target class.
   * @param bool $internal
   *   (optional) Whether the resource type should be internal.
   * @param bool $is_locatable
   *   (optional) Whether the resource type is locatable.
   * @param bool $is_mutable
   *   (optional) Whether the resource type is mutable.
   * @param bool $is_versionable
   *   (optional) Whether the resource type is versionable.
   * @param array $field_mapping
   *   (optional) The field mapping to use.
   */
  public function __construct($entity_type_id, $bundle, $deserialization_target_class, $internal = FALSE, $is_locatable = TRUE, $is_mutable = TRUE, $is_versionable = FALSE, array $field_mapping = []) {
    $this->entityTypeId = $entity_type_id;
    $this->bundle = $bundle;
    $this->deserializationTargetClass = $deserialization_target_class;
    $this->internal = $internal;
    $this->isLocatable = $is_locatable;
    $this->isMutable = $is_mutable;
    $this->isVersionable = $is_versionable;

    $this->typeName = $this->bundle === '?'
      ? 'unknown'
      : sprintf('%s--%s', $this->entityTypeId, $this->bundle);

    $this->fields = array_keys($field_mapping);
    $this->disabledFields = array_keys(array_filter($field_mapping, function ($v) {
      return $v === FALSE;
    }));
    $this->fieldMapping = array_filter($field_mapping, 'is_string');
    $this->invertedFieldMapping = array_flip($this->fieldMapping);
  }

  /**
   * Sets the relatable resource types.
   *
   * @param array $relatable_resource_types
   *   The resource types with which this resource type may have a relationship.
   *   The array should be a multi-dimensional array keyed by public field name
   *   whose values are an array of resource types. There may be duplicate
   *   across resource types across fields, but not within a field.
   */
  public function setRelatableResourceTypes(array $relatable_resource_types) {
    $this->relatableResourceTypes = $relatable_resource_types;
  }

  /**
   * Get all resource types with which this type may have a relationship.
   *
   * @return array
   *   The relatable resource types, keyed by relationship field names.
   *
   * @see self::setRelatableResourceTypes()
   */
  public function getRelatableResourceTypes() {
    if (!isset($this->relatableResourceTypes)) {
      throw new \LogicException("setRelatableResourceTypes() must be called before getting relatable resource types.");
    }
    return $this->relatableResourceTypes;
  }

  /**
   * Get all resource types with which the given field may have a relationship.
   *
   * @param string $field_name
   *   The public field name.
   *
   * @return \Drupal\jsonapi\ResourceType\ResourceType[]
   *   The relatable JSON:API resource types.
   *
   * @see self::getRelatableResourceTypes()
   */
  public function getRelatableResourceTypesByField($field_name) {
    $relatable_resource_types = $this->getRelatableResourceTypes();
    return isset($relatable_resource_types[$field_name]) ?
      $relatable_resource_types[$field_name] :
      [];
  }

  /**
   * Get the resource path.
   *
   * @return string
   *   The path to access this resource type. Default: /entity_type_id/bundle.
   *
   * @see jsonapi.base_path
   */
  public function getPath() {
    return sprintf('/%s/%s', $this->getEntityTypeId(), $this->getBundle());
  }

}
