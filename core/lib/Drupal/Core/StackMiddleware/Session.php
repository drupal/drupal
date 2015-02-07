<?php

/**
 * @file
 * Contains \Drupal\Core\StackMiddleware\Session.
 */

namespace Drupal\Core\StackMiddleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Wrap session logic around a HTTP request.
 */
class Session implements HttpKernelInterface {

  /**
   * The wrapped HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Constructs a Session stack middleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   */
  public function __construct(HttpKernelInterface $http_kernel, SessionInterface $session) {
    $this->httpKernel = $http_kernel;
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    if ($type === self::MASTER_REQUEST) {
      $request->setSession($this->session);
    }

    $result = $this->httpKernel->handle($request, $type, $catch);

    if ($type === self::MASTER_REQUEST && $request->hasSession()) {
      $request->getSession()->save();
    }

    return $result;
  }

}
