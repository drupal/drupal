<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\jsonapi\JsonApiResource\ResourceIdentifier;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;
use Drupal\jsonapi\ResourceType\ResourceType;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Normalizes a Relationship according to the JSON:API specification.
 *
 * Normalizer class for relationship elements. A relationship can be anything
 * that points to an entity in a JSON:API resource.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class ResourceIdentifierNormalizer extends NormalizerBase implements DenormalizerInterface {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = ResourceIdentifier::class;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * RelationshipNormalizer constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   The entity field manager.
   */
  public function __construct(EntityFieldManagerInterface $field_manager) {
    $this->fieldManager = $field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    assert($object instanceof ResourceIdentifier);
    $normalization = [
      'type' => $object->getTypeName(),
      'id' => $object->getId(),
    ];
    if ($object->getMeta()) {
      $normalization['meta'] = $this->serializer->normalize($object->getMeta(), $format, $context);
    }
    return CacheableNormalization::permanent($normalization);
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    // If we get here, it's via a relationship POST/PATCH.
    /** @var \Drupal\jsonapi\ResourceType\ResourceType $resource_type */
    $resource_type = $context['resource_type'];
    $entity_type_id = $resource_type->getEntityTypeId();
    $field_definitions = $this->fieldManager->getFieldDefinitions(
      $entity_type_id,
      $resource_type->getBundle()
    );
    if (empty($context['related']) || empty($field_definitions[$context['related']])) {
      throw new BadRequestHttpException('Invalid or missing related field.');
    }
    /** @var \Drupal\field\Entity\FieldConfig $field_definition */
    $field_definition = $field_definitions[$context['related']];
    // This is typically 'target_id'.
    $item_definition = $field_definition->getItemDefinition();
    $property_key = $item_definition->getMainPropertyName();
    $target_resource_types = $resource_type->getRelatableResourceTypesByField($resource_type->getPublicName($context['related']));
    $target_resource_type_names = array_map(function (ResourceType $resource_type) {
      return $resource_type->getTypeName();
    }, $target_resource_types);

    $is_multiple = $field_definition->getFieldStorageDefinition()->isMultiple();
    $data = $this->massageRelationshipInput($data, $is_multiple);
    $resource_identifiers = array_map(function ($value) use ($property_key, $target_resource_type_names) {
      // Make sure that the provided type is compatible with the targeted
      // resource.
      if (!in_array($value['type'], $target_resource_type_names)) {
        throw new BadRequestHttpException(sprintf(
          'The provided type (%s) does not match the destination resource types (%s).',
          $value['type'],
          implode(', ', $target_resource_type_names)
        ));
      }
      return new ResourceIdentifier($value['type'], $value['id'], $value['meta'] ?? []);
    }, $data['data']);
    if (!ResourceIdentifier::areResourceIdentifiersUnique($resource_identifiers)) {
      throw new BadRequestHttpException('Duplicate relationships are not permitted. Use `meta.arity` to distinguish resource identifiers with matching `type` and `id` values.');
    }
    return $resource_identifiers;
  }

  /**
   * Validates and massages the relationship input depending on the cardinality.
   *
   * @param array $data
   *   The input data from the body.
   * @param bool $is_multiple
   *   Indicates if the relationship is to-many.
   *
   * @return array
   *   The massaged data array.
   */
  protected function massageRelationshipInput(array $data, $is_multiple) {
    if ($is_multiple) {
      if (!is_array($data['data'])) {
        throw new BadRequestHttpException('Invalid body payload for the relationship.');
      }
      // Leave the invalid elements.
      $invalid_elements = array_filter($data['data'], function ($element) {
        return empty($element['type']) || empty($element['id']);
      });
      if ($invalid_elements) {
        throw new BadRequestHttpException('Invalid body payload for the relationship.');
      }
    }
    else {
      // For to-one relationships you can have a NULL value.
      if (is_null($data['data'])) {
        return ['data' => []];
      }
      if (empty($data['data']['type']) || empty($data['data']['id'])) {
        throw new BadRequestHttpException('Invalid body payload for the relationship.');
      }
      $data['data'] = [$data['data']];
    }
    return $data;
  }

}
