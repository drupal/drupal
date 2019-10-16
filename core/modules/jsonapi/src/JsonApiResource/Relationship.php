<?php

namespace Drupal\jsonapi\JsonApiResource;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Url;
use Drupal\jsonapi\JsonApiSpec;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\Routing\Routes;

/**
 * Represents references from one resource object to other resource object(s).
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/jsonapi/issues/3032787
 * @see jsonapi.api.php
 */
class Relationship implements TopLevelDataInterface {

  /**
   * The context resource object of the relationship.
   *
   * A relationship object represents references from a resource object in
   * which it’s defined to other resource objects. Respectively, the "context"
   * of the relationship and the "target(s)" of the relationship.
   *
   * A relationship object's context either comes from the resource object that
   * contains it or, in the case that the relationship object is accessed
   * directly via a relationship URL, from its `self` URL, which should identify
   * the resource to which it belongs.
   *
   * @var \Drupal\jsonapi\JsonApiResource\ResourceObject
   *
   * @see https://jsonapi.org/format/#document-resource-object-relationships
   * @see https://jsonapi.org/recommendations/#urls-relationships
   */
  protected $context;

  /**
   * The data of the relationship object.
   *
   * @var \Drupal\jsonapi\JsonApiResource\RelationshipData
   */
  protected $data;

  /**
   * The relationship's public field name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The relationship object's links.
   *
   * @var \Drupal\jsonapi\JsonApiResource\LinkCollection
   */
  protected $links;

  /**
   * The relationship object's meta member.
   *
   * @var array
   */
  protected $meta;

  /**
   * Relationship constructor.
   *
   * This constructor is protected by design. To create a new relationship, use
   * static::createFromEntityReferenceField().
   *
   * @param string $public_field_name
   *   The public field name of the relationship field.
   * @param \Drupal\jsonapi\JsonApiResource\RelationshipData $data
   *   The relationship data.
   * @param \Drupal\jsonapi\JsonApiResource\LinkCollection $links
   *   Any links for the resource object, if a `self` link is not
   *   provided, one will be automatically added if the resource is locatable
   *   and is not internal.
   * @param array $meta
   *   Any relationship metadata.
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObject $context
   *   The relationship's context resource object. Use the
   *   self::withContext() method to establish a context.
   *
   * @see \Drupal\jsonapi\JsonApiResource\Relationship::createFromEntityReferenceField()
   */
  protected function __construct($public_field_name, RelationshipData $data, LinkCollection $links, array $meta, ResourceObject $context) {
    $this->fieldName = $public_field_name;
    $this->data = $data;
    $this->links = $links->withContext($this);
    $this->meta = $meta;
    $this->context = $context;
  }

  /**
   * Creates a new Relationship from an entity reference field.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObject $context
   *   The context resource object of the relationship to be created.
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field
   *   The entity reference field from which to create the relationship.
   * @param \Drupal\jsonapi\JsonApiResource\LinkCollection $links
   *   (optional) Any extra links for the Relationship, if a `self` link is not
   *   provided, one will be automatically added if the context resource is
   *   locatable and is not internal.
   * @param array $meta
   *   (optional) Any relationship metadata.
   *
   * @return static
   *   An instantiated relationship object.
   */
  public static function createFromEntityReferenceField(ResourceObject $context, EntityReferenceFieldItemListInterface $field, LinkCollection $links = NULL, array $meta = []) {
    $context_resource_type = $context->getResourceType();
    $resource_field = $context_resource_type->getFieldByInternalName($field->getName());
    return new static(
      $resource_field->getPublicName(),
      new RelationshipData(ResourceIdentifier::toResourceIdentifiers($field), $resource_field->hasOne() ? 1 : -1),
      static::buildLinkCollectionFromEntityReferenceField($context, $field, $links ?: new LinkCollection([])),
      $meta,
      $context
    );
  }

  /**
   * Gets context resource object of the relationship.
   *
   * @return \Drupal\jsonapi\JsonApiResource\ResourceObject
   *   The context ResourceObject.
   *
   * @see \Drupal\jsonapi\JsonApiResource\Relationship::$context
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * Gets the relationship object's public field name.
   *
   * @return string
   *   The relationship's field name.
   */
  public function getFieldName() {
    return $this->fieldName;
  }

  /**
   * Gets the relationship object's data.
   *
   * @return \Drupal\jsonapi\JsonApiResource\RelationshipData
   *   The relationship's data.
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Gets the relationship object's links.
   *
   * @return \Drupal\jsonapi\JsonApiResource\LinkCollection
   *   The relationship object's links.
   */
  public function getLinks() {
    return $this->links;
  }

  /**
   * Gets the relationship object's metadata.
   *
   * @return array
   *   The relationship object's metadata.
   */
  public function getMeta() {
    return $this->meta;
  }

  /**
   * {@inheritdoc}
   */
  public function getOmissions() {
    return new OmittedData([]);
  }

  /**
   * {@inheritdoc}
   */
  public function getMergedLinks(LinkCollection $top_level_links) {
    // When directly fetching a relationship object, the relationship object's
    // links become the top-level object's links unless they've been
    // overridden. Overrides are especially important for the `self` link, which
    // must match the link that generated the response. For example, the
    // top-level `self` link might have an `include` query parameter that would
    // be lost otherwise.
    // See https://jsonapi.org/format/#fetching-relationships-responses-200 and
    // https://jsonapi.org/format/#document-top-level.
    return LinkCollection::merge($top_level_links, $this->getLinks()->filter(function ($key) use ($top_level_links) {
      return !$top_level_links->hasLinkWithKey($key);
    })->withContext($top_level_links->getContext()));
  }

  /**
   * {@inheritdoc}
   */
  public function getMergedMeta(array $top_level_meta) {
    return NestedArray::mergeDeep($top_level_meta, $this->getMeta());
  }

  /**
   * Builds a LinkCollection for the given entity reference field.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObject $context
   *   The context resource object of the relationship object.
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field
   *   The entity reference field from which to create the links.
   * @param \Drupal\jsonapi\JsonApiResource\LinkCollection $links
   *   Any extra links for the Relationship, if a `self` link is not provided,
   *   one will be automatically added if the context resource is locatable and
   *   is not internal.
   *
   * @return \Drupal\jsonapi\JsonApiResource\LinkCollection
   *   The built links.
   */
  protected static function buildLinkCollectionFromEntityReferenceField(ResourceObject $context, EntityReferenceFieldItemListInterface $field, LinkCollection $links) {
    $context_resource_type = $context->getResourceType();
    $public_field_name = $context_resource_type->getPublicName($field->getName());
    if ($context_resource_type->isLocatable() && !$context_resource_type->isInternal()) {
      $context_is_versionable = $context_resource_type->isVersionable();
      if (!$links->hasLinkWithKey('self')) {
        $route_name = Routes::getRouteName($context_resource_type, "$public_field_name.relationship.get");
        $self_link = Url::fromRoute($route_name, ['entity' => $context->getId()]);
        if ($context_is_versionable) {
          $self_link->setOption('query', [JsonApiSpec::VERSION_QUERY_PARAMETER => $context->getVersionIdentifier()]);
        }
        $links = $links->withLink('self', new Link(new CacheableMetadata(), $self_link, 'self'));
      }
      $has_non_internal_resource_type = array_reduce($context_resource_type->getRelatableResourceTypesByField($public_field_name), function ($carry, ResourceType $target) {
        return $carry ?: !$target->isInternal();
      }, FALSE);
      // If a `related` link was not provided, automatically generate one from
      // the relationship object to the collection resource with all of the
      // resources targeted by this relationship. However, that link should
      // *not* be generated if all of the relatable resources are internal.
      // That's because, in that case, a route will not exist for it.
      if (!$links->hasLinkWithKey('related') && $has_non_internal_resource_type) {
        $route_name = Routes::getRouteName($context_resource_type, "$public_field_name.related");
        $related_link = Url::fromRoute($route_name, ['entity' => $context->getId()]);
        if ($context_is_versionable) {
          $related_link->setOption('query', [JsonApiSpec::VERSION_QUERY_PARAMETER => $context->getVersionIdentifier()]);
        }
        $links = $links->withLink('related', new Link(new CacheableMetadata(), $related_link, 'related'));
      }
    }
    return $links;
  }

}
