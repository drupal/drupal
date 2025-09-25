<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\DependencyInjection\Fixture;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Stub of http_middleware class taking a service closure for the inner kernel.
 */
class TestClosureHttpMiddlewareClass implements HttpKernelInterface {

  public function __construct(protected readonly \Closure $inner) {
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = TRUE): Response {
    return new Response();
  }

}
