<?php

namespace Drupal\Core\StackMiddleware;

use Drupal\Core\DrupalKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Prepares the environment after page caching ran.
 */
class KernelPreHandle implements HttpKernelInterface {

  public function __construct(
    protected HttpKernelInterface $httpKernel,
    protected DrupalKernelInterface $drupalKernel,
    protected RequestStack $requestStack,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = TRUE): Response {
    // \Drupal\Core\DrupalKernel::preHandle() pushes requests to the stack.
    $this->drupalKernel->preHandle($request);

    try {
      return $this->httpKernel->handle($request, $type, $catch);
    }
    finally {
      // Main requests are popped in \Drupal\Core\DrupalKernel::terminate().
      if ($type !== self::MAIN_REQUEST) {
        $this->requestStack->pop();
      }
    }
  }

}
