<?php

namespace Drupal\Core\Form;

use Symfony\Component\HttpFoundation\Response;

/**
 * Custom exception to break out of the main request and enforce a response.
 */
class EnforcedResponseException extends \Exception {

  /**
   * Constructs a new enforced response exception.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response to be enforced.
   * @param string $message
   *   (optional) The exception message.
   * @param int $code
   *   (optional) A user defined exception code.
   * @param \Throwable $previous
   *   (optional) The previous exception for nested exceptions.
   */
  public function __construct(
    protected Response $response,
    $message = "",
    $code = 0,
    ?\Throwable $previous = NULL,
  ) {
    parent::__construct($message, $code, $previous);
  }

  /**
   * Return the response to be enforced.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response to be enforced.
   */
  public function getResponse() {
    return $this->response;
  }

}
