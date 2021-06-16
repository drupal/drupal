<?php

namespace Drupal\jsonapi\JsonApiResource;

use Drupal\Component\Assertion\Inspector;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * To be used when the primary data is `errors`.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 *
 * (The spec says the top-level `data` and `errors` members MUST NOT coexist.)
 * @see http://jsonapi.org/format/#document-top-level
 *
 * @see http://jsonapi.org/format/#error-objects
 */
class ErrorCollection implements \IteratorAggregate {

  /**
   * The HTTP exceptions.
   *
   * @var \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface[]
   */
  protected $errors;

  /**
   * Instantiates an ErrorCollection object.
   *
   * @param \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface[] $errors
   *   The errors.
   */
  public function __construct(array $errors) {
    assert(Inspector::assertAll(function ($error) {
      return $error instanceof HttpExceptionInterface;
    }, $errors));
    $this->errors = $errors;
  }

  /**
   * Returns an iterator for errors.
   *
   * @return \ArrayIterator
   *   An \ArrayIterator instance
   */
  public function getIterator() {
    return new \ArrayIterator($this->errors);
  }

}
