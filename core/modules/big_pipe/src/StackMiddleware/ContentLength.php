<?php

declare(strict_types=1);

namespace Drupal\big_pipe\StackMiddleware;

use Drupal\big_pipe\Render\BigPipeResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Defines a big pipe middleware that removes Content-Length headers.
 */
class ContentLength implements HttpKernelInterface {

  /**
   * Constructs a new ContentLength instance.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $httpKernel
   *   The wrapped HTTP kernel.
   */
  public function __construct(
    protected readonly HttpKernelInterface $httpKernel,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = TRUE): Response {
    $response = $this->httpKernel->handle($request, $type, $catch);
    if (!$response instanceof BigPipeResponse) {
      return $response;
    }
    $response->headers->remove('Content-Length');
    return $response;
  }

}
