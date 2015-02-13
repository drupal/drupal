<?php

/**
 * @file
 * Contains \Drupal\Core\StackMiddleware\Session.
 */

namespace Drupal\Core\StackMiddleware;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Wrap session logic around a HTTP request.
 *
 * Note, the session service is not injected into this class in order to prevent
 * premature initialization of session storage (database). Instead the session
 * service is retrieved from the container only when handling the request.
 */
class Session implements HttpKernelInterface {

  use ContainerAwareTrait;

  /**
   * The wrapped HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The session service name.
   *
   * @var string
   */
  protected $sessionServiceName;

  /**
   * Constructs a Session stack middleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   * @param string $service_name
   *   The name of the session service, defaults to "session".
   */
  public function __construct(HttpKernelInterface $http_kernel, $service_name = 'session') {
    $this->httpKernel = $http_kernel;
    $this->sessionServiceName = $service_name;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    if ($type === self::MASTER_REQUEST && PHP_SAPI !== 'cli') {
      $request->setSession($this->container->get($this->sessionServiceName));
    }

    $result = $this->httpKernel->handle($request, $type, $catch);

    if ($type === self::MASTER_REQUEST && $request->hasSession()) {
      $request->getSession()->save();
    }

    return $result;
  }

}
