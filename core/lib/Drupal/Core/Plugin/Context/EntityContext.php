<?php

namespace Drupal\Core\Plugin\Context;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Class to provide a specific entity context.
 */
class EntityContext extends Context {

  /**
   * Gets a context from an entity type ID.
   *
   * @param string $entity_type_id
   *   Entity type ID from which a definition will be derived.
   * @param string $label
   *   (optional) The label of the context.
   *
   * @return static
   */
  public static function fromEntityTypeId($entity_type_id, $label = NULL) {
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    return static::fromEntityType($entity_type, $label);
  }

  /**
   * Gets a context from an entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   Entity type from which a definition will be derived.
   * @param string $label
   *   (optional) The label of the context.
   *
   * @return static
   */
  public static function fromEntityType(EntityTypeInterface $entity_type, $label = NULL) {
    $definition = EntityContextDefinition::fromEntityType($entity_type);
    if ($label) {
      $definition->setLabel($label);
    }
    return new static($definition);
  }

  /**
   * Gets a context object from an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity that provides a context.
   * @param string $label
   *   (optional) The label of the context.
   *
   * @return \Drupal\Core\Plugin\Context\EntityContext
   */
  public static function fromEntity(EntityInterface $entity, $label = NULL) {
    $context = static::fromEntityType($entity->getEntityType(), $label);
    $context->setContextValue($entity);
    return $context;
  }

}
