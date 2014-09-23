<?php

/**
 * @file
 * Contains \Drupal\system\Tests\HttpKernel\StackKernelIntegrationTest.
 */

namespace Drupal\system\Tests\HttpKernel;

use Drupal\simpletest\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

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
  }

  /**
   * Tests a request.
   */
  public function testRequest() {
    $request = new Request();
    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel */
    $http_kernel = \Drupal::service('http_kernel');
    $http_kernel->handle($request);

    $this->assertEqual($request->attributes->get('_hello'), 'world');
    $this->assertEqual($request->attributes->get('_previous_optional_argument'), 'test_argument');
  }

}

