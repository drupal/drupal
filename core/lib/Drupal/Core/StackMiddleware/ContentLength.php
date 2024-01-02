<?php

namespace Drupal\Core\StackMiddleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Adds a Content-Length HTTP header to responses.
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
  ) {}

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = TRUE): Response {
    $response = $this->httpKernel->handle($request, $type, $catch);
    if ($response->isInformational() || $response->isEmpty()) {
      return $response;
    }

    if ($response->headers->has('Transfer-Encoding')) {
      return $response;
    }

    // Drupal cannot set the correct content length header when there is a
    // server error.
    if ($response->isServerError()) {
      return $response;
    }

    $content = $response->getContent();
    if ($content === FALSE) {
      return $response;
    }

    $response->headers->set('Content-Length', strlen($content), TRUE);
    return $response;
  }

}
