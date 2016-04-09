<?php

namespace Drupal\KernelTests\RequestProcessing;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests redirects on exception pages.
 *
 * @group request_processing
 */
class RedirectOnExceptionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'test_page_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    \Drupal::service('router.builder')->rebuild();
  }

  public function testRedirectOn404() {
    \Drupal::configFactory()->getEditable('system.site')
      ->set('page.404', '/test-http-response-exception/' . Response::HTTP_PERMANENTLY_REDIRECT)
      ->save();

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel */
    $http_kernel = \Drupal::service('http_kernel');

    // Foo doesn't exist, so this triggers the 404 page.
    $request = Request::create('/foo');
    $response = $http_kernel->handle($request);
    $this->assertEquals(Response::HTTP_PERMANENTLY_REDIRECT, $response->getStatusCode());
  }

}
