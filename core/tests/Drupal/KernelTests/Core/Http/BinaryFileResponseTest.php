<?php

namespace Drupal\KernelTests\Core\Http;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the headers added by BinaryFileResponse.
 *
 * @group Http
 */
class BinaryFileResponseTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['binary_file_response_test'];

  /**
   *
   * @dataProvider providerTestCalculatedContentType
   */
  public function testCalculatedContentType($path, $content_type) {
    $query = ['relative_file_url' => $path];
    $request = Request::create('/binary_file_response_test/download', 'GET', $query);

    $response = \Drupal::service('http_kernel')->handle($request);
    $response->prepare($request);

    $this->assertSame($content_type, $response->headers->get('Content-Type'));
  }

  /**
   * @return array
   */
  public function providerTestCalculatedContentType() {
    $data = [];
    $data[] = ['core/misc/print.css', 'text/css'];
    $data[] = ['core/misc/checkbox.js', 'application/javascript'];
    $data[] = ['core/misc/tree.png', 'image/png'];
    return $data;
  }

}
