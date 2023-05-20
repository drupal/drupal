<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Url;
use Drupal\jsonapi\JsonApiResource\ResourceIdentifier;
use Drupal\jsonapi\JsonApiResource\ResourceIdentifierInterface;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiSpec;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;
use Drupal\jsonapi\ResourceType\ResourceTypeRelationship;
use Drupal\jsonapi\Routing\Routes;

/**
 * Normalizer class specific for entity reference field objects.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class EntityReferenceFieldNormalizer extends FieldNormalizer {

  /**
   * {@inheritdoc}
   */
  public function normalize($field, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    assert($field instanceof EntityReferenceFieldItemListInterface);
    // Build the relationship object based on the Entity Reference and normalize
    // that object instead.
    $resource_identifiers = array_filter(ResourceIdentifier::toResourceIdentifiers($field->filterEmptyItems()), function (ResourceIdentifierInterface $resource_identifier) {
      return !$resource_identifier->getResourceType()->isInternal();
    });
    $normalized_items = CacheableNormalization::aggregate($this->serializer->normalize($resource_identifiers, $format, $context));
    assert($context['resource_object'] instanceof ResourceObject);
    $resource_relationship = $context['resource_object']->getResourceType()->getFieldByInternalName($field->getName());
    assert($resource_relationship instanceof ResourceTypeRelationship);
    $link_cacheability = new CacheableMetadata();
    $links = array_map(function (Url $link) use ($link_cacheability) {
      $href = $link->setAbsolute()->toString(TRUE);
      $link_cacheability->addCacheableDependency($href);
      return ['href' => $href->getGeneratedUrl()];
    }, static::getRelationshipLinks($context['resource_object'], $resource_relationship));
    $data_normalization = $normalized_items->getNormalization();
    $normalization = [
      // Empty 'to-one' relationships must be NULL.
      // Empty 'to-many' relationships must be an empty array.
      // @link http://jsonapi.org/format/#document-resource-object-linkage
      'data' => $resource_relationship->hasOne() ? array_shift($data_normalization) : $data_normalization,
    ];
    if (!empty($links)) {
      $normalization['links'] = $links;
    }
    return (new CacheableNormalization($normalized_items, $normalization))->withCacheableDependency($link_cacheability);
  }

  /**
   * Gets the links for the relationship.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObject $relationship_context
   *   The JSON:API resource object context of the relationship.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRelationship $resource_relationship
   *   The resource type relationship field.
   *
   * @return array
   *   The relationship's links.
   */
  public static function getRelationshipLinks(ResourceObject $relationship_context, ResourceTypeRelationship $resource_relationship) {
    $resource_type = $relationship_context->getResourceType();
    if ($resource_type->isInternal() || !$resource_type->isLocatable()) {
      return [];
    }
    $public_field_name = $resource_relationship->getPublicName();
    $relationship_route_name = Routes::getRouteName($resource_type, "$public_field_name.relationship.get");
    $links = [
      'self' => Url::fromRoute($relationship_route_name, ['entity' => $relationship_context->getId()]),
    ];
    if (static::hasNonInternalResourceType($resource_type->getRelatableResourceTypesByField($public_field_name))) {
      $related_route_name = Routes::getRouteName($resource_type, "$public_field_name.related");
      $links['related'] = Url::fromRoute($related_route_name, ['entity' => $relationship_context->getId()]);
    }
    if ($resource_type->isVersionable()) {
      $version_query_parameter = [JsonApiSpec::VERSION_QUERY_PARAMETER => $relationship_context->getVersionIdentifier()];
      $links['self']->setOption('query', $version_query_parameter);
      if (isset($links['related'])) {
        $links['related']->setOption('query', $version_query_parameter);
      }
    }
    return $links;
  }

  /**
   * Determines if a given list of resource types contains a non-internal type.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType[] $resource_types
   *   The JSON:API resource types to evaluate.
   *
   * @return bool
   *   FALSE if every resource type is internal, TRUE otherwise.
   */
  protected static function hasNonInternalResourceType(array $resource_types) {
    foreach ($resource_types as $resource_type) {
      if (!$resource_type->isInternal()) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      EntityReferenceFieldItemListInterface::class => TRUE,
    ];
  }

}
