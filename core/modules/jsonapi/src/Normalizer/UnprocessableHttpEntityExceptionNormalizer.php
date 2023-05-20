<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\jsonapi\Exception\UnprocessableHttpEntityException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Normalizes and UnprocessableHttpEntityException.
 *
 * Normalizes an UnprocessableHttpEntityException in compliance with the JSON
 * API specification. A source pointer is added to help client applications
 * report validation errors, for example on an Entity edit form.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 *
 * @see http://jsonapi.org/format/#error-objects
 */
class UnprocessableHttpEntityExceptionNormalizer extends HttpExceptionNormalizer {

  /**
   * {@inheritdoc}
   */
  protected function buildErrorObjects(HttpException $exception) {
    /** @var \Drupal\jsonapi\Exception\UnprocessableHttpEntityException $exception */
    $errors = parent::buildErrorObjects($exception);
    $error = $errors[0];
    unset($error['links']);

    $errors = [];
    $violations = $exception->getViolations();
    $entity_violations = $violations->getEntityViolations();
    foreach ($entity_violations as $violation) {
      /** @var \Symfony\Component\Validator\ConstraintViolation $violation */
      $error['detail'] = 'Entity is not valid: '
        . $violation->getMessage();
      $error['source']['pointer'] = '/data';
      $errors[] = $error;
    }

    $entity = $violations->getEntity();
    foreach ($violations->getFieldNames() as $field_name) {
      $field_violations = $violations->getByField($field_name);
      $cardinality = $entity->get($field_name)
        ->getFieldDefinition()
        ->getFieldStorageDefinition()
        ->getCardinality();

      foreach ($field_violations as $violation) {
        /** @var \Symfony\Component\Validator\ConstraintViolation $violation */
        $error['detail'] = $violation->getPropertyPath() . ': '
          . PlainTextOutput::renderFromHtml($violation->getMessage());

        $pointer = '/data/attributes/'
          . str_replace('.', '/', $violation->getPropertyPath());
        if ($cardinality == 1) {
          // Remove erroneous '/0/' index for single-value fields.
          $pointer = str_replace("/data/attributes/$field_name/0/", "/data/attributes/$field_name/", $pointer);
        }
        $error['source']['pointer'] = $pointer;

        $errors[] = $error;
      }
    }

    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      UnprocessableHttpEntityException::class => TRUE,
    ];
  }

}
