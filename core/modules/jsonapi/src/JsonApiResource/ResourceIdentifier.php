<?php

namespace Drupal\jsonapi\JsonApiResource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\TypedData\DataReferenceDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInternalPropertiesHelper;
use Drupal\jsonapi\ResourceType\ResourceType;

/**
 * Represents a JSON:API resource identifier object.
 *
 * The official JSON:API JSON-Schema document requires that no two resource
 * identifier objects are duplicates, however Drupal allows multiple entity
 * reference items to the same entity. Here, these are termed "parallel"
 * relationships (as in "parallel edges" of a graph).
 *
 * This class adds a concept of an @code arity @endcode member under each its
 * @code meta @endcode object. The value of this member is an integer that is
 * incremented by 1 (starting from 0) for each repeated resource identifier
 * sharing a common @code type @endcode and @code id @endcode.
 *
 * There are a number of helper methods to process the logic of dealing with
 * resource identifies with and without arity.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 *
 * @see http://jsonapi.org/format/#document-resource-object-relationships
 * @see https://github.com/json-api/json-api/pull/1156#issuecomment-325377995
 * @see https://www.drupal.org/project/drupal/issues/2864680
 */
class ResourceIdentifier implements ResourceIdentifierInterface {

  const ARITY_KEY = 'arity';

  /**
   * The JSON:API resource type name.
   *
   * @var string
   */
  protected $resourceTypeName;

  /**
   * The JSON:API resource type.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceType
   */
  protected $resourceType;

  /**
   * The resource ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The relationship's metadata.
   *
   * @var array
   */
  protected $meta;

  /**
   * ResourceIdentifier constructor.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType|string $resource_type
   *   The JSON:API resource type or a JSON:API resource type name.
   * @param string $id
   *   The resource ID.
   * @param array $meta
   *   Any metadata for the ResourceIdentifier.
   */
  public function __construct($resource_type, $id, array $meta = []) {
    assert(is_string($resource_type) || $resource_type instanceof ResourceType);
    assert(!isset($meta[static::ARITY_KEY]) || is_int($meta[static::ARITY_KEY]) && $meta[static::ARITY_KEY] >= 0);
    $this->resourceTypeName = is_string($resource_type) ? $resource_type : $resource_type->getTypeName();
    $this->id = $id;
    $this->meta = $meta;
    if (!is_string($resource_type)) {
      $this->resourceType = $resource_type;
    }
  }

  /**
   * Gets the ResourceIdentifier's JSON:API resource type name.
   *
   * @return string
   *   The JSON:API resource type name.
   */
  public function getTypeName() {
    return $this->resourceTypeName;
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceType() {
    if (!isset($this->resourceType)) {
      /** @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository */
      $resource_type_repository = \Drupal::service('jsonapi.resource_type.repository');
      $this->resourceType = $resource_type_repository->getByTypeName($this->getTypeName());
    }
    return $this->resourceType;
  }

  /**
   * Gets the ResourceIdentifier's ID.
   *
   * @return string
   *   The ID.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Whether this ResourceIdentifier has an arity.
   *
   * @return int
   *   TRUE if the ResourceIdentifier has an arity, FALSE otherwise.
   */
  public function hasArity() {
    return isset($this->meta[static::ARITY_KEY]);
  }

  /**
   * Gets the ResourceIdentifier's arity.
   *
   * One must check self::hasArity() before calling this method.
   *
   * @return int
   *   The arity.
   */
  public function getArity() {
    assert($this->hasArity());
    return $this->meta[static::ARITY_KEY];
  }

  /**
   * Returns a copy of the given ResourceIdentifier with the given arity.
   *
   * @param int $arity
   *   The new arity; must be a non-negative integer.
   *
   * @return static
   *   A newly created ResourceIdentifier with the given arity, otherwise
   *   the same.
   */
  public function withArity($arity) {
    return new static($this->getResourceType(), $this->getId(), [static::ARITY_KEY => $arity] + $this->getMeta());
  }

  /**
   * Gets the resource identifier objects metadata.
   *
   * @return array
   *   The metadata.
   */
  public function getMeta() {
    return $this->meta;
  }

  /**
   * Determines if two ResourceIdentifiers are the same.
   *
   * This method does not consider parallel relationships with different arity
   * values to be duplicates. For that, use the isParallel() method.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceIdentifier $a
   *   The first ResourceIdentifier object.
   * @param \Drupal\jsonapi\JsonApiResource\ResourceIdentifier $b
   *   The second ResourceIdentifier object.
   *
   * @return bool
   *   TRUE if both relationships reference the same resource and do not have
   *   two distinct arity's, FALSE otherwise.
   *
   *   For example, if $a and $b both reference the same resource identifier,
   *   they can only be distinct if they *both* have an arity and those values
   *   are not the same. If $a or $b does not have an arity, they will be
   *   considered duplicates.
   */
  public static function isDuplicate(ResourceIdentifier $a, ResourceIdentifier $b) {
    return static::compare($a, $b) === 0;
  }

  /**
   * Determines if two ResourceIdentifiers identify the same resource object.
   *
   * This method does not consider arity.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceIdentifier $a
   *   The first ResourceIdentifier object.
   * @param \Drupal\jsonapi\JsonApiResource\ResourceIdentifier $b
   *   The second ResourceIdentifier object.
   *
   * @return bool
   *   TRUE if both relationships reference the same resource, even when they
   *   have differing arity values, FALSE otherwise.
   */
  public static function isParallel(ResourceIdentifier $a, ResourceIdentifier $b) {
    return static::compare($a->withArity(0), $b->withArity(0)) === 0;
  }

  /**
   * Compares ResourceIdentifier objects.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceIdentifier $a
   *   The first ResourceIdentifier object.
   * @param \Drupal\jsonapi\JsonApiResource\ResourceIdentifier $b
   *   The second ResourceIdentifier object.
   *
   * @return int
   *   Returns 0 if $a and $b are duplicate ResourceIdentifiers. If $a and $b
   *   identify the same resource but have distinct arity values, then the
   *   return value will be arity $a minus arity $b. -1 otherwise.
   */
  public static function compare(ResourceIdentifier $a, ResourceIdentifier $b) {
    $result = strcmp(sprintf('%s:%s', $a->getTypeName(), $a->getId()), sprintf('%s:%s', $b->getTypeName(), $b->getId()));
    // If type and ID do not match, return their ordering.
    if ($result !== 0) {
      return $result;
    }
    // If both $a and $b have an arity, then return the order by arity.
    // Otherwise, they are considered equal.
    return $a->hasArity() && $b->hasArity()
      ? $a->getArity() - $b->getArity()
      : 0;
  }

  /**
   * Deduplicates an array of ResourceIdentifier objects.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceIdentifier[] $resource_identifiers
   *   The list of ResourceIdentifiers to deduplicate.
   *
   * @return \Drupal\jsonapi\JsonApiResource\ResourceIdentifier[]
   *   A deduplicated array of ResourceIdentifier objects.
   *
   * @see self::isDuplicate()
   */
  public static function deduplicate(array $resource_identifiers) {
    return array_reduce(array_slice($resource_identifiers, 1), function ($deduplicated, $current) {
      assert($current instanceof static);
      return array_merge($deduplicated, array_reduce($deduplicated, function ($duplicate, $previous) use ($current) {
        return $duplicate ?: static::isDuplicate($previous, $current);
      }, FALSE) ? [] : [$current]);
    }, array_slice($resource_identifiers, 0, 1));
  }

  /**
   * Determines if an array of ResourceIdentifier objects is duplicate free.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceIdentifier[] $resource_identifiers
   *   The list of ResourceIdentifiers to assess.
   *
   * @return bool
   *   Whether all the given resource identifiers are unique.
   */
  public static function areResourceIdentifiersUnique(array $resource_identifiers) {
    return count($resource_identifiers) === count(static::deduplicate($resource_identifiers));
  }

  /**
   * Creates a ResourceIdentifier object.
   *
   * @param \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item
   *   The entity reference field item from which to create the relationship.
   * @param int $arity
   *   (optional) The arity of the relationship.
   *
   * @return self
   *   A new ResourceIdentifier object.
   */
  public static function toResourceIdentifier(EntityReferenceItem $item, $arity = NULL) {
    $property_name = static::getDataReferencePropertyName($item);
    $target = $item->get($property_name)->getValue();
    if ($target === NULL) {
      return static::getVirtualOrMissingResourceIdentifier($item);
    }
    assert($target instanceof EntityInterface);
    /** @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository */
    $resource_type_repository = \Drupal::service('jsonapi.resource_type.repository');
    $resource_type = $resource_type_repository->get($target->getEntityTypeId(), $target->bundle());
    // Remove unwanted properties from the meta value, usually 'entity'
    // and 'target_id'.
    $properties = TypedDataInternalPropertiesHelper::getNonInternalProperties($item);
    $meta = array_diff_key($properties, array_flip([$property_name, $item->getDataDefinition()->getMainPropertyName()]));
    if (!is_null($arity)) {
      $meta[static::ARITY_KEY] = $arity;
    }
    return new static($resource_type, $target->uuid(), $meta);
  }

  /**
   * Creates an array of ResourceIdentifier objects.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items
   *   The entity reference field items from which to create the relationship
   *   array.
   *
   * @return self[]
   *   An array of new ResourceIdentifier objects with appropriate arity values.
   */
  public static function toResourceIdentifiers(EntityReferenceFieldItemListInterface $items) {
    $relationships = [];
    foreach ($items->filterEmptyItems() as $item) {
      // Create a ResourceIdentifier from the field item. This will make it
      // comparable with all previous field items. Here, it is assumed that the
      // resource identifier is unique so it has no arity. If a parallel
      // relationship is encountered, it will be assigned later.
      $relationship = static::toResourceIdentifier($item);
      if ($relationship->getResourceType()->isInternal()) {
        continue;
      }
      // Now, iterate over the previously seen resource identifiers in reverse
      // order. Reverse order is important so that when a parallel relationship
      // is encountered, it will have the highest arity value so the current
      // relationship's arity value can simply be incremented by one.
      /** @var \Drupal\jsonapi\JsonApiResource\ResourceIdentifier $existing */
      foreach (array_reverse($relationships, TRUE) as $index => $existing) {
        $is_parallel = static::isParallel($existing, $relationship);
        if ($is_parallel) {
          // A parallel relationship has been found. If the previous
          // relationship does not have an arity, it must now be assigned an
          // arity of 0.
          if (!$existing->hasArity()) {
            $relationships[$index] = $existing->withArity(0);
          }
          // Since the new ResourceIdentifier is parallel, it must have an arity
          // assigned to it that is the arity of the last parallel
          // relationship's arity + 1.
          $relationship = $relationship->withArity($relationships[$index]->getArity() + 1);
          break;
        }
      }
      // Finally, append the relationship to the list of ResourceIdentifiers.
      $relationships[] = $relationship;
    }
    return $relationships;
  }

  /**
   * Creates an array of ResourceIdentifier objects with arity on every value.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items
   *   The entity reference field items from which to create the relationship
   *   array.
   *
   * @return self[]
   *   An array of new ResourceIdentifier objects with appropriate arity values.
   *   Unlike self::toResourceIdentifiers(), this method does not omit arity
   *   when an identifier is not parallel to any other identifier.
   */
  public static function toResourceIdentifiersWithArityRequired(EntityReferenceFieldItemListInterface $items) {
    return array_map(function (ResourceIdentifier $identifier) {
      return $identifier->hasArity() ? $identifier : $identifier->withArity(0);
    }, static::toResourceIdentifiers($items));
  }

  /**
   * Creates a ResourceIdentifier object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity from which to create the resource identifier.
   *
   * @return self
   *   A new ResourceIdentifier object.
   */
  public static function fromEntity(EntityInterface $entity) {
    /** @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository */
    $resource_type_repository = \Drupal::service('jsonapi.resource_type.repository');
    $resource_type = $resource_type_repository->get($entity->getEntityTypeId(), $entity->bundle());
    return new static($resource_type, $entity->uuid());
  }

  /**
   * Helper method to determine which field item property contains an entity.
   *
   * @param \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item
   *   The entity reference item for which to determine the entity property
   *   name.
   *
   * @return string
   *   The property name which has an entity as its value.
   */
  protected static function getDataReferencePropertyName(EntityReferenceItem $item) {
    foreach ($item->getDataDefinition()->getPropertyDefinitions() as $property_name => $property_definition) {
      if ($property_definition instanceof DataReferenceDefinitionInterface) {
        return $property_name;
      }
    }
  }

  /**
   * Creates a ResourceIdentifier for a NULL or FALSE entity reference item.
   *
   * @param \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item
   *   The entity reference field item.
   *
   * @return self
   *   A new ResourceIdentifier object.
   */
  protected static function getVirtualOrMissingResourceIdentifier(EntityReferenceItem $item) {
    $resource_type_repository = \Drupal::service('jsonapi.resource_type.repository');
    $property_name = static::getDataReferencePropertyName($item);
    $value = $item->get($property_name)->getValue();
    assert($value === NULL);
    $field = $item->getParent();
    assert($field instanceof EntityReferenceFieldItemListInterface);
    $host_entity = $field->getEntity();
    assert($host_entity instanceof EntityInterface);
    $resource_type = $resource_type_repository->get($host_entity->getEntityTypeId(), $host_entity->bundle());
    assert($resource_type instanceof ResourceType);
    $relatable_resource_types = $resource_type->getRelatableResourceTypesByField($resource_type->getPublicName($field->getName()));
    assert(!empty($relatable_resource_types));
    $get_metadata = function ($type) {
      return [
        'links' => [
          'help' => [
            'href' => "https://www.drupal.org/docs/8/modules/json-api/core-concepts#$type",
            'meta' => [
              'about' => "Usage and meaning of the '$type' resource identifier.",
            ],
          ],
        ],
      ];
    };
    $resource_type = reset($relatable_resource_types);
    // A non-empty entity reference field that refers to a non-existent entity
    // is not a data integrity problem. For example, Term entities' "parent"
    // entity reference field uses target_id zero to refer to the non-existent
    // "<root>" term. And references to entities that no longer exist are not
    // cleaned up by Drupal; hence we map it to a "missing" resource.
    if ($field->getFieldDefinition()->getSetting('target_type') === 'taxonomy_term' && $item->get('target_id')->getCastedValue() === 0) {
      if (count($relatable_resource_types) !== 1) {
        throw new \RuntimeException('Relationships to virtual resources are possible only if a single resource type is relatable.');
      }
      return new static($resource_type, 'virtual', $get_metadata('virtual'));
    }
    else {
      // In case of a dangling reference, it is impossible to determine which
      // resource type it used to reference, because that requires knowing the
      // referenced bundle, which Drupal does not store.
      // If we can reliably determine the resource type of the dangling
      // reference, use it; otherwise conjure a fake resource type out of thin
      // air, one that indicates we don't know the bundle.
      $resource_type = count($relatable_resource_types) > 1
        ? new ResourceType('?', '?', '')
        : reset($relatable_resource_types);
      return new static($resource_type, 'missing', $get_metadata('missing'));
    }
  }

}
