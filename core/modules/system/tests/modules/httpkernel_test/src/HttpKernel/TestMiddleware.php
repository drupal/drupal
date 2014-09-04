<?php

/**
 * @file
 * Contains \Drupal\httpkernel_test\HttpKernel\TestMiddleware.
 */

namespace Drupal\httpkernel_test\HttpKernel;

use Symfony\Component\HttpFoundation\Request;
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
 protected $kernel;

  /**
   * An optional argument.
   *
   * @var mixed
   */
  protected $optionalArgument;

  /**
   * Constructs a new TestMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $kernel
   *   The decorated kernel.
   * @param mixed $optional_argument
   *   (optional) An optional argument.
   */
  public function __construct(HttpKernelInterface $kernel, $optional_argument = NULL) {
    $this->kernel = $kernel;
    $this->optionalArgument = $optional_argument;
 }

 /**
  * {@inheritdoc}
  */
 public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
   $request->attributes->set('_hello', 'world');
   if ($request->attributes->has('_optional_argument')) {
     $request->attributes->set('_previous_optional_argument', $request->attributes->get('_optional_argument'));
   }
   elseif (isset($this->optionalArgument)) {
     $request->attributes->set('_optional_argument', $this->optionalArgument);
   }

   return $this->kernel->handle($request, $type, $catch);
 }

}
