<?php

namespace Drupal\rest\Plugin\rest\resource;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @internal
 * @todo Consider making public in https://www.drupal.org/node/2300677
 */
trait EntityResourceValidationTrait {

  /**
   * Verifies that an entity does not violate any validation constraints.
   *
   * The validation errors will be filtered to not include fields to which the
   * current user does not have access and if $fields_to_validate is provided
   * will only include fields in that array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to validate.
   * @param string[] $fields_to_validate
   *   (optional) An array of field names. If specified, filters the violations
   *   list to include only this set of fields.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
   *   If validation errors are found.
   */
  protected function validate(EntityInterface $entity, array $fields_to_validate = []) {
    // @todo Update this check in https://www.drupal.org/node/2300677.
    if (!$entity instanceof FieldableEntityInterface) {
      return;
    }
    $violations = $entity->validate();

    // Remove violations of inaccessible fields as they cannot stem from our
    // changes.
    $violations->filterByFieldAccess();

    if ($fields_to_validate) {
      // Filter violations by explicitly provided array of field names.
      $violations->filterByFields(array_diff(array_keys($entity->getFieldDefinitions()), $fields_to_validate));
    }

    if ($violations->count() > 0) {
      $message = "Unprocessable Entity: validation failed.\n";
      foreach ($violations as $violation) {
        // We strip every HTML from the error message to have a nicer to read
        // message on REST responses.
        $message .= $violation->getPropertyPath() . ': ' . PlainTextOutput::renderFromHtml($violation->getMessage()) . "\n";
      }
      throw new UnprocessableEntityHttpException($message);
    }
  }

}
