<?php

/**
 * @file
 * Contains \Drupal\system\Tests\HttpKernel\StackKernelIntegrationTest.
 */

namespace Drupal\system\Tests\HttpKernel;

use Drupal\simpletest\KernelTestBase;
use Drupal\Core\Url;
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
  public static $modules = array('httpkernel_test', 'system');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'router');
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

}
