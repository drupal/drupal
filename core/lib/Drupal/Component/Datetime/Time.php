<?php

namespace Drupal\Component\Datetime;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a class for obtaining system time.
 *
 * While the normal use case of this class expects that a Request object is
 * available from the RequestStack, it is still possible to use it without, for
 * example for early bootstrap containers or for unit tests. In those cases,
 * the class will access global variables or set a proxy request time in order
 * to return the request time.
 */
class Time implements TimeInterface {

  /**
   * The request stack.
   */
  protected ?RequestStack $requestStack;

  /**
   * A proxied request time if the request time is not available.
   */
  protected float $proxyRequestTime;

  /**
   * Constructs a Time object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack|null $request_stack
   *   (Optional) The request stack.
   */
  public function __construct(RequestStack $request_stack = NULL) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestTime() {
    $request = $this->requestStack ? $this->requestStack->getCurrentRequest() : NULL;
    if ($request) {
      return $request->server->get('REQUEST_TIME');
    }
    // If this is called prior to the request being pushed to the stack fallback
    // to built-in globals (if available) or the system time.
    return $_SERVER['REQUEST_TIME'] ?? $this->getProxyRequestTime();
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestMicroTime() {
    $request = $this->requestStack ? $this->requestStack->getCurrentRequest() : NULL;
    if ($request) {
      return $request->server->get('REQUEST_TIME_FLOAT');
    }
    // If this is called prior to the request being pushed to the stack fallback
    // to built-in globals (if available) or the system time.
    return $_SERVER['REQUEST_TIME_FLOAT'] ?? $this->getProxyRequestMicroTime();
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

  /**
   * Returns a mimic of the timestamp of the current request.
   *
   * @return int
   *   A value returned by time().
   */
  protected function getProxyRequestTime(): int {
    if (!isset($this->proxyRequestTime)) {
      $this->proxyRequestTime = $this->getCurrentMicroTime();
    }
    return (int) $this->proxyRequestTime;
  }

  /**
   * Returns a mimic of the timestamp of the current request.
   *
   * @return float
   *   A value returned by microtime().
   */
  protected function getProxyRequestMicroTime(): float {
    if (!isset($this->proxyRequestTime)) {
      $this->proxyRequestTime = $this->getCurrentMicroTime();
    }
    return $this->proxyRequestTime;
  }

}
