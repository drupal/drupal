<?php

namespace Drupal\Core\Form;

use Symfony\Component\HttpFoundation\Response;

/**
 * A wrapper containing a response which is to be enforced upon delivery.
 *
 * The FormBuilder throws an EnforcedResponseException whenever a form
 * desires to explicitly set a response object. Exception handlers capable of
 * setting the response should extract the response object of such an exception
 * using EnforcedResponse::createFromException(). Then wrap it into an
 * EnforcedResponse object and replace the original response with the wrapped
 * response.
 *
 * @see Drupal\Core\EventSubscriber\EnforcedFormResponseSubscriber::onKernelException()
 * @see Drupal\Core\EventSubscriber\DefaultExceptionSubscriber::createHtmlResponse()
 * @see Drupal\Core\EventSubscriber\DefaultExceptionHtmlSubscriber::createResponse()
 */
class EnforcedResponse extends Response {

  /**
   * The wrapped response object.
   *
   * @var \Symfony\Component\HttpFoundation\Response
   */
  protected $response;

  /**
   * Constructs a new enforced response from the given exception.
   *
   * Note that it is necessary to traverse the exception chain when searching
   * for an enforced response. Otherwise it would be impossible to find an
   * exception thrown from within a twig template.
   *
   * @param \Exception $e
   *   The exception where the enforced response is to be extracted from.
   *
   * @return static|null
   *   The enforced response or NULL if the exception chain does not contain a
   *   \Drupal\Core\Form\EnforcedResponseException exception.
   */
  public static function createFromException(\Exception $e) {
    while ($e) {
      if ($e instanceof EnforcedResponseException) {
        return new static($e->getResponse());
      }

      $e = $e->getPrevious();
    }
  }

  /**
   * Constructs an enforced response.
   *
   * Use EnforcedResponse::createFromException() instead.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response to wrap.
   */
  public function __construct(Response $response) {
    parent::__construct('', 500);
    $this->response = $response;
  }

  /**
   * Returns the wrapped response.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The wrapped response.
   */
  public function getResponse() {
    return $this->response;
  }

}
