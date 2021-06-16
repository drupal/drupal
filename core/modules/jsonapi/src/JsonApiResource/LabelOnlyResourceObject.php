<?php

namespace Drupal\jsonapi\JsonApiResource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\jsonapi\ResourceType\ResourceType;

/**
 * Value object decorating a ResourceObject; only its label is available.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
final class LabelOnlyResourceObject extends ResourceObject {

  /**
   * The entity represented by this resource object.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public static function createFromEntity(ResourceType $resource_type, EntityInterface $entity, LinkCollection $links = NULL) {
    $resource_object = new static(
      $entity,
      $resource_type,
      $entity->uuid(),
      $resource_type->isVersionable() && $entity instanceof RevisionableInterface ? $entity->getRevisionId() : NULL,
      static::extractFieldsFromEntity($resource_type, $entity),
      static::buildLinksFromEntity($resource_type, $entity, $links ?: new LinkCollection([]))
    );
    $resource_object->setEntity($entity);
    return $resource_object;
  }

  /**
   * Gets the decorated entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The label for which to only normalize its label.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Sets the underlying entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   */
  protected function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected static function extractFieldsFromEntity(ResourceType $resource_type, EntityInterface $entity) {
    $fields = parent::extractFieldsFromEntity($resource_type, $entity);
    $public_label_field_name = $resource_type->getPublicName(static::getLabelFieldName($entity));
    return array_intersect_key($fields, [$public_label_field_name => TRUE]);
  }

}
