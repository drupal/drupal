<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\jsonapi\ResourceType\ResourceType;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Converts a JSON:API array structure into a Drupal entity object.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
final class ContentEntityDenormalizer extends EntityDenormalizerBase {

  /**
   * Prepares the input data to create the entity.
   *
   * @param array $data
   *   The input data to modify.
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   Contains the info about the resource type.
   * @param string $format
   *   Format the given data was extracted from.
   * @param array $context
   *   Options available to the denormalizer.
   *
   * @return array
   *   The modified input data.
   */
  protected function prepareInput(array $data, ResourceType $resource_type, $format, array $context) {
    $data_internal = [];

    $field_map = $this->fieldManager->getFieldMap()[$resource_type->getEntityTypeId()];

    $entity_type_id = $resource_type->getEntityTypeId();
    $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type_id);
    $bundle_key = $entity_type_definition->getKey('bundle');
    $uuid_key = $entity_type_definition->getKey('uuid');

    // User resource objects contain a read-only attribute that is not a real
    // field on the user entity type.
    // @see \Drupal\jsonapi\JsonApiResource\ResourceObject::extractContentEntityFields()
    // @todo Eliminate this special casing in https://www.drupal.org/project/drupal/issues/3079254.
    if ($entity_type_id === 'user') {
      $data = array_diff_key($data, array_flip([$resource_type->getPublicName('display_name')]));
    }

    // Translate the public fields into the entity fields.
    foreach ($data as $public_field_name => $field_value) {
      $internal_name = $resource_type->getInternalName($public_field_name);

      // Skip any disabled field, except the always required bundle key and
      // required-in-case-of-PATCHing uuid key.
      // @see \Drupal\jsonapi\ResourceType\ResourceTypeRepository::getFieldMapping()
      if ($resource_type->hasField($internal_name) && !$resource_type->isFieldEnabled($internal_name) && $bundle_key !== $internal_name && $uuid_key !== $internal_name) {
        continue;
      }

      if (!isset($field_map[$internal_name]) || !in_array($resource_type->getBundle(), $field_map[$internal_name]['bundles'], TRUE)) {
        throw new UnprocessableEntityHttpException(sprintf(
          'The attribute %s does not exist on the %s resource type.',
          $internal_name,
          $resource_type->getTypeName()
        ));
      }

      $field_type = $field_map[$internal_name]['type'];
      $field_class = $this->pluginManager->getDefinition($field_type)['list_class'];

      $field_denormalization_context = array_merge($context, [
        'field_type' => $field_type,
        'field_name' => $internal_name,
        'field_definition' => $this->fieldManager->getFieldDefinitions($resource_type->getEntityTypeId(), $resource_type->getBundle())[$internal_name],
      ]);
      $data_internal[$internal_name] = $this->serializer->denormalize($field_value, $field_class, $format, $field_denormalization_context);
    }

    return $data_internal;
  }

  /**
   * {@inheritdoc}
   */
  public function hasCacheableSupportsMethod(): bool {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use getSupportedTypes() instead. See https://www.drupal.org/node/3359695', E_USER_DEPRECATED);

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      ContentEntityInterface::class => TRUE,
    ];
  }

}
