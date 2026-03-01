<?php

declare(strict_types=1);

namespace Drupal\http_kernel_test\Controller;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DrupalKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * A test controller.
 */
class TestController implements ContainerInjectionInterface {
  use AutowireTrait;

  public function __construct(protected DrupalKernelInterface $kernel) {
  }

  /**
   * Return an empty response.
   */
  public function get() {
    return new Response();
  }

  /**
   * Return an empty response.
   */
  public function subRequest(): Response {
    $sub_request = Request::create('/http-kernel-test-sub-sub-request');
    return $this->kernel->handle($sub_request, KernelInterface::SUB_REQUEST);
  }

}
