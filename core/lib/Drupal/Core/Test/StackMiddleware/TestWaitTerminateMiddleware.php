<?php

namespace Drupal\Core\Test\StackMiddleware;

use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Acquire a lock to signal request termination to the test runner.
 */
class TestWaitTerminateMiddleware implements HttpKernelInterface {

  /**
   * Constructs a test wait terminate stack middleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $httpKernel
   *   The decorated kernel.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state server.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   */
  public function __construct(
    protected HttpKernelInterface $httpKernel,
    protected StateInterface $state,
    protected LockBackendInterface $lock,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = TRUE): Response {
    $result = $this->httpKernel->handle($request, $type, $catch);

    if ($this->state->get('drupal.test_wait_terminate')) {
      // Set a header on the response to instruct the test runner that it must
      // await the lock. Note that the lock acquired here is automatically
      // released from within a shutdown function.
      $this->lock->acquire('test_wait_terminate');
      $result->headers->set('X-Drupal-Wait-Terminate', '1');
    }

    return $result;
  }

}
