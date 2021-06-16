<?php

namespace Drupal\jsonapi\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\jsonapi\Exception\UnprocessableHttpEntityException;

/**
 * Provides a method to validate an entity.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
trait EntityValidationTrait {

  /**
   * Verifies that an entity does not violate any validation constraints.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param string[] $field_names
   *   (optional) An array of field names. If specified, filters the violations
   *   list to include only this set of fields. Defaults to NULL,
   *   which means that all violations will be reported.
   *
   * @throws \Drupal\jsonapi\Exception\UnprocessableHttpEntityException
   *   Thrown when violations remain after filtering.
   *
   * @see \Drupal\rest\Plugin\rest\resource\EntityResourceValidationTrait::validate()
   */
  protected static function validate(EntityInterface $entity, array $field_names = NULL) {
    if (!$entity instanceof FieldableEntityInterface) {
      return;
    }

    $violations = $entity->validate();

    // Remove violations of inaccessible fields as they cannot stem from our
    // changes.
    $violations->filterByFieldAccess();

    // Filter violations based on the given fields.
    if ($field_names !== NULL) {
      $violations->filterByFields(
        array_diff(array_keys($entity->getFieldDefinitions()), $field_names)
      );
    }

    if (count($violations) > 0) {
      // Instead of returning a generic 400 response we use the more specific
      // 422 Unprocessable Entity code from RFC 4918. That way clients can
      // distinguish between general syntax errors in bad serializations (code
      // 400) and semantic errors in well-formed requests (code 422).
      // @see \Drupal\jsonapi\Normalizer\UnprocessableHttpEntityExceptionNormalizer
      $exception = new UnprocessableHttpEntityException();
      $exception->setViolations($violations);
      throw $exception;
    }
  }

}
