<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItemInterface;
use Drupal\jsonapi\JsonApiResource\Relationship;
use Drupal\jsonapi\JsonApiSpec;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\serialization\Normalizer\SchematicNormalizerTrait;

/**
 * Normalizes a JSON:API relationship object.
 *
 * @internal
 */
class RelationshipNormalizer extends NormalizerBase {

  use SchematicNormalizerTrait;

  /**
   * Constructor.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resourceTypeRepository
   *   Resource type repository.
   */
  public function __construct(
    protected ResourceTypeRepositoryInterface $resourceTypeRepository,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function doNormalize($object, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    assert($object instanceof Relationship);
    return CacheableNormalization::aggregate([
      'data' => $this->serializer->normalize($object->getData(), $format, $context),
      'links' => $this->serializer->normalize($object->getLinks(), $format, $context)->omitIfEmpty(),
      'meta' => CacheableNormalization::permanent($object->getMeta())->omitIfEmpty(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getNormalizationSchema(mixed $object, array $context = []): array {
    assert($object instanceof Relationship);
    $schema = [
      'allOf' => [
        ['$ref' => JsonApiSpec::SUPPORTED_SPECIFICATION_JSON_SCHEMA . '#/definitions/relationship'],
      ],
    ];
    $field_definition = $object->getContext()->getField($object->getFieldName())?->getFieldDefinition();
    $item_class = $field_definition?->getItemDefinition()->getClass();
    assert($item_class, sprintf('The context ResourceObject for Relationship being normalized is missing field %s.', $object->getFieldName()));
    if (!$item_class || !is_subclass_of($item_class, EntityReferenceItemInterface::class)) {
      return $schema;
    }
    $targets = $item_class::getReferenceableBundles($field_definition);
    $target_types = array_reduce(array_keys($targets), function (array $carry, string $entity_type_id) use ($targets) {
      foreach ($targets[$entity_type_id] as $bundle) {
        // Even if a resource is internal, it can be referenced.
        if ((!$resource = $this->resourceTypeRepository->get($entity_type_id, $bundle)) || in_array($resource->getTypeName(), $carry)) {
          continue;
        }
        $carry[] = $resource->getTypeName();
      }
      return $carry;
    }, []);
    if ($target_types) {
      $schema['properties']['type'] = [
        'oneOf' => array_map(fn(string $resource_type_name) => ['const' => $resource_type_name], $target_types),
      ];
    }
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      Relationship::class => TRUE,
    ];
  }

}
