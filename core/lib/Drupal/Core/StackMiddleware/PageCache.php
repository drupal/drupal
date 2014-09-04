<?php

/**
 * @file
 * Contains \Drupal\Core\StackMiddleware\PageCache.
 */

namespace Drupal\Core\StackMiddleware;

use Drupal\Core\DrupalKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Executes the page caching before the main kernel takes over the request.
 */
class PageCache implements HttpKernelInterface {

  /**
   * The wrapped HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The main Drupal kernel.
   *
   * @var \Drupal\Core\DrupalKernelInterface
   */
  protected $drupalKernel;

  /**
   * Constructs a ReverseProxyMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   * @param \Drupal\Core\DrupalKernelInterface $drupal_kernel
   *   The main Drupal kernel.
   */
  public function __construct(HttpKernelInterface $http_kernel, DrupalKernelInterface $drupal_kernel) {
    $this->httpKernel = $http_kernel;
    $this->drupalKernel = $drupal_kernel;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    $this->drupalKernel->handlePageCache($request);

    return $this->httpKernel->handle($request, $type, $catch);
  }

}
