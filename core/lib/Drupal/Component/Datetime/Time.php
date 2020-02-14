<?php

namespace Drupal\Component\Datetime;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a class for obtaining system time.
 */
class Time implements TimeInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a Time object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestTime() {
    $request = $this->requestStack->getCurrentRequest();
    if ($request) {
      return $request->server->get('REQUEST_TIME');
    }
    // If this is called prior to the request being pushed to the stack fallback
    // to built-in globals (if available) or the system time.
    return $_SERVER['REQUEST_TIME'] ?? $this->getCurrentTime();
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestMicroTime() {
    $request = $this->requestStack->getCurrentRequest();
    if ($request) {
      return $request->server->get('REQUEST_TIME_FLOAT');
    }
    // If this is called prior to the request being pushed to the stack fallback
    // to built-in globals (if available) or the system time.
    return $_SERVER['REQUEST_TIME_FLOAT'] ?? $this->getCurrentMicroTime();
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentTime() {
    return time();
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentMicroTime() {
    return microtime(TRUE);
  }

}
