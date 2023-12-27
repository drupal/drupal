<?php

namespace Drupal\Core\StackMiddleware;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
  public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = TRUE): Response {
    // Initialize and start a session for web requests. Command line tools and
    // the parent site in functional tests must continue to use the ephemeral
    // session initialized and started in DrupalKernel::preHandle().
    if ($type === self::MAIN_REQUEST && PHP_SAPI !== 'cli') {
      $this->initializePersistentSession($request);
    }

    $result = $this->httpKernel->handle($request, $type, $catch);

    if ($type === self::MAIN_REQUEST && PHP_SAPI !== 'cli' && $request->hasSession()) {
      $request->getSession()->save();
    }

    return $result;
  }

  /**
   * Initializes a session backed by persistent store and puts it on the request.
   *
   * Sessions for web requests need to be backed by a persistent session store
   * and a real session handler (responsible for session cookie management).
   * In contrast, a simple in-memory store is sufficient for command line tools
   * and tests. Hence, the persistent session should only ever be placed on web
   * requests while command line tools and the parent site in functional tests
   * must continue to use the ephemeral session initialized in
   * DrupalKernel::preHandle().
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @see \Drupal\Core\DrupalKernel::preHandle()
   */
  protected function initializePersistentSession(Request $request): void {
    $session = $this->container->get($this->sessionServiceName);
    $session->start();
    $request->setSession($session);
  }

}
