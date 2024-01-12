<?php

namespace Drupal\Tests\Core\DependencyInjection\Fixture;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

/**
 * Stub of http_middleware class that is declared final.
 */
final class FinalTestHttpMiddlewareClass implements HttpKernelInterface, TerminableInterface {

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = TRUE): Response {
    return new Response();
  }

  /**
   * {@inheritdoc}
   *
   * phpcs:ignore Drupal.Commenting.FunctionComment.VoidReturn
   * @return void
   */
  public function terminate(Request $request, Response $response) {}

}
