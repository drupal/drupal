<?php

declare(strict_types=1);

namespace Drupal\http_kernel_test\HttpKernel;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Provides a test middleware.
 */
class TestMiddleware implements HttpKernelInterface {

  /**
   * The decorated kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * An optional argument.
   *
   * @var mixed
   */
  protected $optionalArgument;

  /**
   * Constructs a new TestMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   * @param mixed $optional_argument
   *   (optional) An optional argument.
   */
  public function __construct(HttpKernelInterface $http_kernel, $optional_argument = NULL) {
    $this->httpKernel = $http_kernel;
    $this->optionalArgument = $optional_argument;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = TRUE): Response {
    $request->attributes->set('_hello', 'world');
    if ($request->attributes->has('_optional_argument')) {
      $request->attributes->set('_previous_optional_argument', $request->attributes->get('_optional_argument'));
    }
    elseif (isset($this->optionalArgument)) {
      $request->attributes->set('_optional_argument', $this->optionalArgument);
    }

    return $this->httpKernel->handle($request, $type, $catch);
  }

}
