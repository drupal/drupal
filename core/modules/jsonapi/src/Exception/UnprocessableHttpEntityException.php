<?php

namespace Drupal\jsonapi\Exception;

use Drupal\Core\Entity\EntityConstraintViolationListInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * A class to represent a 422 - Unprocessable Entity Exception.
 *
 * The HTTP 422 status code is used when the server sees:-
 *
 *  The content type of the request is correct.
 *  The syntax of the request is correct.
 *  BUT was unable to process the contained instruction.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class UnprocessableHttpEntityException extends HttpException {

  use DependencySerializationTrait;

  /**
   * The constraint violations associated with this exception.
   *
   * @var \Drupal\Core\Entity\EntityConstraintViolationListInterface
   */
  protected $violations;

  /**
   * UnprocessableHttpEntityException constructor.
   *
   * @param \Exception|null $previous
   *   The pervious error, if any, associated with the request.
   * @param array $headers
   *   The headers associated with the request.
   * @param int $code
   *   The HTTP status code associated with the request. Defaults to zero.
   */
  public function __construct(?\Exception $previous = NULL, array $headers = [], $code = 0) {
    parent::__construct(422, "Unprocessable Entity: validation failed.", $previous, $headers, $code);
  }

  /**
   * Gets the constraint violations associated with this exception.
   *
   * @return \Drupal\Core\Entity\EntityConstraintViolationListInterface
   *   The constraint violations.
   */
  public function getViolations() {
    return $this->violations;
  }

  /**
   * Sets the constraint violations associated with this exception.
   *
   * @param \Drupal\Core\Entity\EntityConstraintViolationListInterface $violations
   *   The constraint violations.
   */
  public function setViolations(EntityConstraintViolationListInterface $violations) {
    $this->violations = $violations;
  }

}
