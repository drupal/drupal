<?php

declare(strict_types=1);

namespace Drupal\form_test\StackMiddleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Provides a test middleware which sets a custom response header.
 */
class FormTestMiddleware implements HttpKernelInterface {

  /**
   * Constructs a FormTestMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $httpKernel
   *   The decorated kernel.
   */
  public function __construct(
    protected HttpKernelInterface $httpKernel,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = TRUE): Response {
    $response = $this->httpKernel->handle($request, $type, $catch);
    $response->headers->set('X-Form-Test-Stack-Middleware', 'invoked');
    return $response;
  }

}
