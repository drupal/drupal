<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\HttpKernel;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the stacked kernel functionality.
 */
#[Group('Routing')]
#[RunTestsInSeparateProcesses]
class StackKernelIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['http_kernel_test', 'system'];

  /**
   * Tests a request.
   */
  public function testRequest(): void {
    $request = Request::create((new Url('http_kernel_test.empty'))->toString());
    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel */
    $http_kernel = \Drupal::service('http_kernel');
    $http_kernel->handle($request, HttpKernelInterface::MAIN_REQUEST, FALSE);

    $this->assertEquals('world', $request->attributes->get('_hello'));
    $this->assertEquals('test_argument', $request->attributes->get('_previous_optional_argument'));
  }

  /**
   * Tests that service closure middleware avoids creation of http kernel.
   */
  public function testServiceClosureMiddlewares(): void {
    $this->assertFalse(\Drupal::getContainer()->initialized('http_kernel.basic'));
    \Drupal::service('http_kernel');
    $this->assertTrue(\Drupal::getContainer()->initialized('http_kernel.basic'));

    // Page cache provides a service closure middleware.
    \Drupal::service('module_installer')->install(['page_cache']);

    $this->assertFalse(\Drupal::getContainer()->initialized('http_kernel.basic'));
    \Drupal::service('http_kernel');
    $this->assertFalse(\Drupal::getContainer()->initialized('http_kernel.basic'));
  }

}
