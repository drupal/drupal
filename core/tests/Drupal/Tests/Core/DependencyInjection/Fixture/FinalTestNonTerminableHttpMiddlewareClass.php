<?php

namespace Drupal\Tests\Core\DependencyInjection\Fixture;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Stub of http_middleware class that is declared final but is not terminable.
 */
final class FinalTestNonTerminableHttpMiddlewareClass implements HttpKernelInterface {

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = TRUE): Response {
    return new Response();
  }

}
