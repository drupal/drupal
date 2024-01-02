<?php

declare(strict_types=1);

namespace Drupal\http_middleware_test\StackMiddleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Alters the response before content length is calculated.
 */
final class AlterContentMiddleware implements HttpKernelInterface {

  public function __construct(
    private readonly HttpKernelInterface $httpKernel,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = TRUE): Response {
    $response = $this->httpKernel->handle($request, $type, $catch);
    if (\Drupal::getContainer()->hasParameter('no-alter-content-length') && \Drupal::getContainer()->getParameter('no-alter-content-length')) {
      $response->setContent('<html><body><p>Avocados</p></body></html>');
    }
    return $response;
  }

}
