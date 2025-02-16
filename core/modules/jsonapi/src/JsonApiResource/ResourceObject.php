<?php

namespace Drupal\jsonapi\JsonApiResource;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableDependencyTrait;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\TypedData\TypedDataInternalPropertiesHelper;
use Drupal\Core\Url;
use Drupal\jsonapi\JsonApiSpec;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\Revisions\VersionByRel;
use Drupal\jsonapi\Routing\Routes;
use Drupal\user\UserInterface;

/**
 * Represents a JSON:API resource object.
 *
 * This value object wraps a Drupal entity so that it can carry a JSON:API
 * resource type object alongside it. It also helps abstract away differences
 * between config and content entities within the JSON:API codebase.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class ResourceObject implements CacheableDependencyInterface, ResourceIdentifierInterface {

  use CacheableDependencyTrait;
  use ResourceIdentifierTrait;

  /**
   * The resource object's version identifier.
   *
   * @var string|null
   */
  protected $versionIdentifier;

  /**
   * The object's fields.
   *
   * This refers to "fields" in the JSON:API sense of the word. Config entities
   * do not have real fields, so in that case, this will be an array of values
   * for config entity attributes.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface[]|mixed[]
   */
  protected $fields;

  /**
   * The resource object's links.
   *
   * @var \Drupal\jsonapi\JsonApiResource\LinkCollection
   */
  protected $links;

  /**
   * The resource language.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $language;

  /**
   * ResourceObject constructor.
   *
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $cacheability
   *   The cacheability for the resource object.
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The JSON:API resource type of the resource object.
   * @param string $id
   *   The resource object's ID.
   * @param mixed|null $revision_id
   *   The resource object's version identifier. NULL, if the resource object is
   *   not versionable.
   * @param array $fields
   *   An array of the resource object's fields, keyed by public field name.
   * @param \Drupal\jsonapi\JsonApiResource\LinkCollection $links
   *   The links for the resource object.
   * @param \Drupal\Core\Language\LanguageInterface|null $language
   *   (optional) The resource language.
   */
  public function __construct(CacheableDependencyInterface $cacheability, ResourceType $resource_type, $id, $revision_id, array $fields, LinkCollection $links, ?LanguageInterface $language = NULL) {
    assert(is_null($revision_id) || $resource_type->isVersionable());
    $this->setCacheability($cacheability);
    $this->resourceType = $resource_type;
    $this->resourceIdentifier = new ResourceIdentifier($resource_type, $id);
    $this->versionIdentifier = $revision_id ? 'id:' . $revision_id : NULL;
    $this->fields = $fields;
    $this->links = $links->withContext($this);

    // If the specified language empty it falls back the same way as in the
    // entity system
    // @see \Drupal\Core\Entity\EntityBase::language()
    $this->language = $language ?: new Language(['id' => LanguageInterface::LANGCODE_NOT_SPECIFIED]);
  }

  /**
   * Creates a new ResourceObject from an entity.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The JSON:API resource type of the resource object.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be represented by this resource object.
   * @param \Drupal\jsonapi\JsonApiResource\LinkCollection $links
   *   (optional) Any links for the resource object, if a `self` link is not
   *   provided, one will be automatically added if the resource is locatable
   *   and is not an internal entity.
   *
   * @return static
   *   An instantiated resource object.
   */
  public static function createFromEntity(ResourceType $resource_type, EntityInterface $entity, ?LinkCollection $links = NULL) {
    return new static(
      $entity,
      $resource_type,
      $entity->uuid(),
      $resource_type->isVersionable() && $entity instanceof RevisionableInterface ? $entity->getRevisionId() : NULL,
      static::extractFieldsFromEntity($resource_type, $entity),
      static::buildLinksFromEntity($resource_type, $entity, $links ?: new LinkCollection([])),
      $entity->language()
    );
  }

  /**
   * Whether the resource object has the given field.
   *
   * @param string $public_field_name
   *   A public field name.
   *
   * @return bool
   *   TRUE if the resource object has the given field, FALSE otherwise.
   */
  public function hasField($public_field_name) {
    return isset($this->fields[$public_field_name]);
  }

  /**
   * Gets the given field.
   *
   * @param string $public_field_name
   *   A public field name.
   *
   * @return mixed|\Drupal\Core\Field\FieldItemListInterface|null
   *   The field or NULL if the resource object does not have the given field.
   *
   * @see ::extractFields()
   */
  public function getField($public_field_name) {
    return $this->hasField($public_field_name) ? $this->fields[$public_field_name] : NULL;
  }

  /**
   * Gets the ResourceObject's fields.
   *
   * @return array
   *   The resource object's fields, keyed by public field name.
   *
   * @see ::extractFields()
   */
  public function getFields() {
    return $this->fields;
  }

  /**
   * Gets the ResourceObject's language.
   *
   * @return \Drupal\Core\Language\LanguageInterface
   *   The resource language.
   */
  public function getLanguage(): LanguageInterface {
    return $this->language;
  }

  /**
   * Gets the ResourceObject's links.
   *
   * @return \Drupal\jsonapi\JsonApiResource\LinkCollection
   *   The resource object's links.
   */
  public function getLinks() {
    return $this->links;
  }

  /**
   * Gets a version identifier for the ResourceObject.
   *
   * @return string
   *   The version identifier of the resource object, if the resource type is
   *   versionable.
   */
  public function getVersionIdentifier() {
    if (!$this->resourceType->isVersionable()) {
      throw new \LogicException('Cannot get a version identifier for a non-versionable resource.');
    }
    return $this->versionIdentifier;
  }

  /**
   * Gets a Url for the ResourceObject.
   *
   * @return \Drupal\Core\Url
   *   The URL for the identified resource object.
   *
   * @throws \LogicException
   *   Thrown if the resource object is not locatable.
   *
   * @see \Drupal\jsonapi\ResourceType\ResourceTypeRepository::isLocatableResourceType()
   */
  public function toUrl() {
    foreach ($this->links as $key => $link) {
      if ($key === 'self') {
        $first = reset($link);
        return $first->getUri();
      }
    }
    throw new \LogicException('A Url does not exist for this resource object because its resource type is not locatable.');
  }

  /**
   * Extracts the entity's fields.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The JSON:API resource type of the given entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity from which fields should be extracted.
   *
   * @return mixed|\Drupal\Core\Field\FieldItemListInterface[]
   *   If the resource object represents a content entity, the fields will be
   *   objects satisfying FieldItemListInterface. If it represents a config
   *   entity, the fields will be scalar values or arrays.
   */
  protected static function extractFieldsFromEntity(ResourceType $resource_type, EntityInterface $entity) {
    assert($entity instanceof ContentEntityInterface || $entity instanceof ConfigEntityInterface);
    return $entity instanceof ContentEntityInterface
      ? static::extractContentEntityFields($resource_type, $entity)
      : static::extractConfigEntityFields($resource_type, $entity);
  }

  /**
   * Builds a LinkCollection for the given entity.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The JSON:API resource type of the given entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to build links.
   * @param \Drupal\jsonapi\JsonApiResource\LinkCollection $links
   *   (optional) Any extra links for the resource object, if a `self` link is
   *   not provided, one will be automatically added if the resource is
   *   locatable and is not an internal entity.
   *
   * @return \Drupal\jsonapi\JsonApiResource\LinkCollection
   *   The built links.
   */
  protected static function buildLinksFromEntity(ResourceType $resource_type, EntityInterface $entity, LinkCollection $links) {
    if ($resource_type->isLocatable() && !$resource_type->isInternal()) {
      $self_url = Url::fromRoute(Routes::getRouteName($resource_type, 'individual'), ['entity' => $entity->uuid()]);
      if ($resource_type->isVersionable()) {
        assert($entity instanceof RevisionableInterface);
        if (!$links->hasLinkWithKey('self')) {
          // If the resource is versionable, the `self` link should be the exact
          // link for the represented version. This helps a client track
          // revision changes and to disambiguate resource objects with the same
          // `type` and `id` in a `version-history` collection.
          $self_with_version_url = $self_url->setOption('query', [JsonApiSpec::VERSION_QUERY_PARAMETER => 'id:' . $entity->getRevisionId()]);
          $links = $links->withLink('self', new Link(new CacheableMetadata(), $self_with_version_url, 'self'));
        }
        if (!$entity->isDefaultRevision()) {
          $latest_version_url = $self_url->setOption('query', [JsonApiSpec::VERSION_QUERY_PARAMETER => 'rel:' . VersionByRel::LATEST_VERSION]);
          $links = $links->withLink(VersionByRel::LATEST_VERSION, new Link(new CacheableMetadata(), $latest_version_url, VersionByRel::LATEST_VERSION));
        }
        if (!$entity->isLatestRevision()) {
          $working_copy_url = $self_url->setOption('query', [JsonApiSpec::VERSION_QUERY_PARAMETER => 'rel:' . VersionByRel::WORKING_COPY]);
          $links = $links->withLink(VersionByRel::WORKING_COPY, new Link(new CacheableMetadata(), $working_copy_url, VersionByRel::WORKING_COPY));
        }
      }
      if (!$links->hasLinkWithKey('self')) {
        $links = $links->withLink('self', new Link(new CacheableMetadata(), $self_url, 'self'));
      }
    }
    return $links;
  }

  /**
   * Extracts a content entity's fields.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The JSON:API resource type of the given entity.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The config entity from which fields should be extracted.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface[]
   *   The fields extracted from a content entity.
   */
  protected static function extractContentEntityFields(ResourceType $resource_type, ContentEntityInterface $entity) {
    $output = [];
    $fields = TypedDataInternalPropertiesHelper::getNonInternalProperties($entity->getTypedData());
    // Filter the array based on the field names.
    $enabled_field_names = array_filter(
      array_keys($fields),
      [$resource_type, 'isFieldEnabled']
    );

    // Special handling for user entities that allows a JSON:API user agent to
    // access the display name of a user. For example, this is useful when
    // displaying the name of a node's author.
    // @todo Eliminate this special casing in https://www.drupal.org/project/drupal/issues/3079254.
    $entity_type = $entity->getEntityType();
    if ($entity_type->id() == 'user' && $resource_type->isFieldEnabled('display_name')) {
      assert($entity instanceof UserInterface);
      $display_name = $resource_type->getPublicName('display_name');
      $output[$display_name] = $entity->getDisplayName();
    }

    // Return a sub-array of $output containing the keys in $enabled_fields.
    $input = array_intersect_key($fields, array_flip($enabled_field_names));
    foreach ($input as $field_name => $field_value) {
      $public_field_name = $resource_type->getPublicName($field_name);
      $output[$public_field_name] = $field_value;
    }

    return $output;
  }

  /**
   * Determines the entity type's (internal) label field name.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity from which fields should be extracted.
   *
   * @return string
   *   The label field name.
   */
  protected static function getLabelFieldName(EntityInterface $entity) {
    $label_field_name = $entity->getEntityType()->getKey('label');
    // Special handling for user entities that allows a JSON:API user agent to
    // access the display name of a user. This is useful when displaying the
    // name of a node's author.
    // @see \Drupal\jsonapi\JsonApiResource\ResourceObject::extractContentEntityFields()
    // @todo Eliminate this special casing in https://www.drupal.org/project/drupal/issues/3079254.
    if ($entity->getEntityTypeId() === 'user') {
      $label_field_name = 'display_name';
    }
    return $label_field_name;
  }

  /**
   * Extracts a config entity's fields.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The JSON:API resource type of the given entity.
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The config entity from which fields should be extracted.
   *
   * @return array
   *   The fields extracted from a config entity.
   */
  protected static function extractConfigEntityFields(ResourceType $resource_type, ConfigEntityInterface $entity) {
    $enabled_public_fields = [];
    $fields = $entity->toArray();
    // Filter the array based on the field names.
    $enabled_field_names = array_filter(array_keys($fields), function ($internal_field_name) use ($resource_type) {
      // Config entities have "fields" which aren't known to the resource type,
      // these fields should not be excluded because they cannot be enabled or
      // disabled.
      return !$resource_type->hasField($internal_field_name) || $resource_type->isFieldEnabled($internal_field_name);
    });
    // Return a sub-array of $output containing the keys in $enabled_fields.
    $input = array_intersect_key($fields, array_flip($enabled_field_names));
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    foreach ($input as $field_name => $field_value) {
      $public_field_name = $resource_type->getPublicName($field_name);
      $enabled_public_fields[$public_field_name] = $field_value;
    }
    return $enabled_public_fields;
  }

}
