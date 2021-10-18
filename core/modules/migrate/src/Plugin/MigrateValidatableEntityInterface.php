<?php

namespace Drupal\migrate\Plugin;

use Drupal\Core\Entity\FieldableEntityInterface;

// cspell:ignore validatable

/**
 * To implement by a destination plugin that should provide entity validation.
 *
 * @ingroup migration
 */
interface MigrateValidatableEntityInterface {

  /**
   * Returns a state of whether an entity needs to be validated before saving.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to check for required validation.
   *
   * @return bool
   *   A state of whether an entity needs to be validated.
   */
  public function isEntityValidationRequired(FieldableEntityInterface $entity);

  /**
   * Validates the entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to validate.
   *
   * @throws \Drupal\migrate\Exception\EntityValidationException
   *   When the validation didn't succeed.
   */
  public function validateEntity(FieldableEntityInterface $entity);

}
