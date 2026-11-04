<?php

declare(strict_types=1);

namespace Drupal\accept_header_routing_test;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Example implementation of "accept header"-based content negotiation.
 */
class AcceptHeaderMiddleware implements HttpKernelInterface {

  /**
   * Constructs a new AcceptHeaderMiddleware instance.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $httpKernel
   *   The app.
   */
  public function __construct(
    protected HttpKernelInterface $httpKernel,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = TRUE): Response {
    $mapping = [
      'application/json' => 'json',
      'application/xml' => 'xml',
      'text/html' => 'html',
    ];

    $accept = $request->headers->get('Accept') ?: ['text/html'];
    if (isset($mapping[$accept[0]])) {
      $request->setRequestFormat($mapping[$accept[0]]);
    }

    return $this->httpKernel->handle($request, $type, $catch);
  }

}
