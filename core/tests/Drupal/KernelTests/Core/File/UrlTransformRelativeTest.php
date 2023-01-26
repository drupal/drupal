<?php

namespace Drupal\KernelTests\Core\File;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests URL transform to relative.
 *
 * @group Utility
 */
class UrlTransformRelativeTest extends KernelTestBase {

  protected static $modules = ['file_test'];

  /**
   * Tests transformRelative() function.
   *
   * @dataProvider providerFileUrlTransformRelative
   */
  public function testFileUrlTransformRelative($host, $port, $https, $url, $expected) {

    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['SERVER_ADDR'] = '127.0.0.1';
    $_SERVER['SERVER_PORT'] = $port;
    $_SERVER['SERVER_SOFTWARE'] = NULL;
    $_SERVER['SERVER_NAME'] = $host;
    $_SERVER['REQUEST_URI'] = '/';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['SCRIPT_FILENAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
    $_SERVER['HTTP_USER_AGENT'] = 'Drupal command line';
    $_SERVER['HTTPS'] = $https;

    $request = Request::createFromGlobals();
    \Drupal::requestStack()->push($request);

    $this->assertSame($expected, \Drupal::service('file_url_generator')->transformRelative($url));
  }

  public function providerFileUrlTransformRelative() {
    $data = [
      'http' => [
        'example.com',
        80,
        '',
        'http://example.com/page',
        '/page',
      ],
      'https' => [
        'example.com',
        443,
        'on',
        'https://example.com/page',
        '/page',
      ],
      'http 8080' => [
        'example.com',
        8080,
        '',
        'https://example.com:8080/page',
        '/page',
      ],
      'https 8443' => [
        'example.com',
        8443,
        'on',
        'https://example.com:8443/page',
        '/page',
      ],
      'http no dot' => [
        'example.com',
        80,
        '',
        'http://exampleXcom/page',
        'http://exampleXcom/page',
      ],
      'http files on different port than the web request' => [
        'example.com',
        80,
        '',
        'http://example.com:9000/page',
        'http://example.com:9000/page',
      ],
      'https files on different port than the web request' => [
        'example.com',
        443,
        'on',
        'https://example.com:8443/page',
        'https://example.com:8443/page',
      ],
    ];
    return $data;
  }

}
