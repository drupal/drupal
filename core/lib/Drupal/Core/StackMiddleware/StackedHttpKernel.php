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
  private $kernel;

  /**
   * A set of middlewares that are wrapped around this kernel.
   *
   * @var array
   */
  private $middlewares = [];

  /**
   * Constructs a stacked HTTP kernel.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $kernel
   *   The decorated kernel.
   * @param array $middlewares
   *   An array of previous middleware services.
   */
  public function __construct(HttpKernelInterface $kernel, array $middlewares) {
    $this->kernel = $kernel;
    $this->middlewares = $middlewares;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = HttpKernelInterface::MAIN_REQUEST, $catch = TRUE): Response {
    return $this->kernel->handle($request, $type, $catch);
  }

  /**
   * {@inheritdoc}
   */
  public function terminate(Request $request, Response $response) {
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
