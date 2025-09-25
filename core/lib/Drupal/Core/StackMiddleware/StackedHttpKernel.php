<?php

namespace Drupal\Core\StackMiddleware;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a stacked HTTP kernel.
 *
 * Copied from https://github.com/stackphp/builder/ with added compatibility
 * for Symfony 6.
 *
 * @see \Drupal\Core\DependencyInjection\Compiler\StackedKernelPass
 */
class StackedHttpKernel implements HttpKernelInterface, TerminableInterface {

  /**
   * The decorated kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  private $httpKernel;

  /**
   * A set of middlewares that are wrapped around this kernel.
   *
   * @var iterable<\Symfony\Component\HttpKernel\HttpKernelInterface>
   */
  private $middlewares = [];

  /**
   * Constructs a stacked HTTP kernel.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   * @param iterable<\Symfony\Component\HttpKernel\HttpKernelInterface> $middlewares
   *   An array of previous middleware services.
   */
  public function __construct(HttpKernelInterface $http_kernel, iterable $middlewares) {
    if (is_array($middlewares)) {
      @trigger_error('Calling ' . __METHOD__ . '() with an array of $middlewares is deprecated in drupal:11.3.0 and it will throw an error in drupal:12.0.0. Pass in a lazy iterator instead. See https://www.drupal.org/node/3538740', E_USER_DEPRECATED);
    }
    $this->httpKernel = $http_kernel;
    $this->middlewares = $middlewares;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = HttpKernelInterface::MAIN_REQUEST, $catch = TRUE): Response {
    return $this->httpKernel->handle($request, $type, $catch);
  }

  /**
   * {@inheritdoc}
   */
  public function terminate(Request $request, Response $response): void {
    $previous = NULL;
    foreach ($this->middlewares as $kernel) {
      // If the previous kernel was terminable we can assume this middleware
      // has already been called.
      if (!$previous instanceof TerminableInterface && $kernel instanceof TerminableInterface) {
        $kernel->terminate($request, $response);
      }
      $previous = $kernel;
    }
  }

}
