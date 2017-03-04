<?php

namespace Drupal\KernelTests\Core\HttpKernel;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the stacked kernel functionality.
 *
 * @group Routing
 */
class StackKernelIntegrationTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['httpkernel_test', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Tests a request.
   */
  public function testRequest() {
    $request = Request::create((new Url('httpkernel_test.empty'))->toString());
    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel */
    $http_kernel = \Drupal::service('http_kernel');
    $http_kernel->handle($request, HttpKernelInterface::MASTER_REQUEST, FALSE);

    $this->assertEqual($request->attributes->get('_hello'), 'world');
    $this->assertEqual($request->attributes->get('_previous_optional_argument'), 'test_argument');
  }

  /**
   * Tests that late middlewares are automatically flagged lazy.
   */
  public function testLazyLateMiddlewares() {
    $this->assertFalse($this->container->getDefinition('http_middleware.reverse_proxy')->isLazy(), 'lazy flag on http_middleware.reverse_proxy definition is not set');
    $this->assertFalse($this->container->getDefinition('http_middleware.kernel_pre_handle')->isLazy(), 'lazy flag on http_middleware.kernel_pre_handle definition is not set');
    $this->assertFalse($this->container->getDefinition('http_middleware.session')->isLazy(), 'lazy flag on http_middleware.session definition is not set');
    $this->assertFalse($this->container->getDefinition('http_kernel.basic')->isLazy(), 'lazy flag on http_kernel.basic definition is not set');

    \Drupal::service('module_installer')->install(['page_cache']);
    $this->container = \Drupal::service('kernel')->rebuildContainer();

    $this->assertFalse($this->container->getDefinition('http_middleware.reverse_proxy')->isLazy(), 'lazy flag on http_middleware.reverse_proxy definition is not set');
    $this->assertFalse($this->container->getDefinition('http_middleware.page_cache')->isLazy(), 'lazy flag on http_middleware.page_cache definition is not set');
    $this->assertTrue($this->container->getDefinition('http_middleware.kernel_pre_handle')->isLazy(), 'lazy flag on http_middleware.kernel_pre_handle definition is automatically set if page_cache is enabled.');
    $this->assertTrue($this->container->getDefinition('http_middleware.session')->isLazy(), 'lazy flag on http_middleware.session definition is automatically set if page_cache is enabled.');
    $this->assertTrue($this->container->getDefinition('http_kernel.basic')->isLazy(), 'lazy flag on http_kernel.basic definition is automatically set if page_cache is enabled.');
  }

}
