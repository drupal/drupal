<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\File;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Tests URL transform to relative.
 *
 * @group Utility
 */
class UrlTransformRelativeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file_test'];

  /**
   * Tests transformRelative() function.
   *
   * @dataProvider providerFileUrlTransformRelative
   */
  public function testFileUrlTransformRelative($host, $port, $https, $base_path, $root_relative, $url, $expected): void {

    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['SERVER_ADDR'] = '127.0.0.1';
    $_SERVER['SERVER_PORT'] = $port;
    $_SERVER['SERVER_SOFTWARE'] = NULL;
    $_SERVER['SERVER_NAME'] = $host;
    $_SERVER['REQUEST_URI'] = "{$base_path}/";
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['SCRIPT_NAME'] = "{$base_path}/index.php";
    $_SERVER['SCRIPT_FILENAME'] = "{$base_path}/index.php";
    $_SERVER['PHP_SELF'] = "{$base_path}/index.php";
    $_SERVER['HTTP_USER_AGENT'] = 'Drupal command line';
    $_SERVER['HTTPS'] = $https;

    $request = Request::createFromGlobals();
    $request->setSession(new Session(new MockArraySessionStorage()));
    \Drupal::requestStack()->push($request);

    $this->assertSame($expected, \Drupal::service('file_url_generator')->transformRelative($url, $root_relative));
  }

  public static function providerFileUrlTransformRelative() {
    $data = [
      'http' => [
        'example.com',
        80,
        '',
        '',
        TRUE,
        'http://example.com/page',
        '/page',
      ],
      'http with base path and root relative' => [
        'example.com',
        80,
        '',
        '/~foo',
        TRUE,
        'http://example.com/~foo/page',
        '/~foo/page',
      ],
      'http with base path and not root relative' => [
        'example.com',
        80,
        '',
        '/~foo',
        FALSE,
        'http://example.com/~foo/page',
        '/page',
      ],
      'http with weird base path and root relative' => [
        'example.com',
        80,
        '',
        '/~foo$.*!',
        TRUE,
        'http://example.com/~foo$.*!/page',
        '/~foo$.*!/page',
      ],
      'http with weird base path and not root relative' => [
        'example.com',
        80,
        '',
        '/~foo$.*!',
        FALSE,
        'http://example.com/~foo$.*!/page',
        '/page',
      ],
      'http frontpage' => [
        'example.com',
        80,
        '',
        '',
        TRUE,
        'http://example.com',
        '/',
      ],
      'http frontpage with a slash' => [
        'example.com',
        80,
        '',
        '',
        TRUE,
        'http://example.com/',
        '/',
      ],
      'https on http' => [
        'example.com',
        80,
        '',
        '',
        TRUE,
        'https://example.com/page',
        '/page',
      ],
      'https' => [
        'example.com',
        443,
        'on',
        '',
        TRUE,
        'https://example.com/page',
        '/page',
      ],
      'https frontpage' => [
        'example.com',
        443,
        'on',
        '',
        TRUE,
        'https://example.com',
        '/',
      ],
      'https frontpage with a slash' => [
        'example.com',
        443,
        'on',
        '',
        TRUE,
        'https://example.com/',
        '/',
      ],
      'http with path containing special chars' => [
        'example.com',
        80,
        '',
        '',
        TRUE,
        'http://example.com/~page$.*!',
        '/~page$.*!',
      ],
      'http 8080' => [
        'example.com',
        8080,
        '',
        '',
        TRUE,
        'https://example.com:8080/page',
        '/page',
      ],
      'https 8443' => [
        'example.com',
        8443,
        'on',
        '',
        TRUE,
        'https://example.com:8443/page',
        '/page',
      ],
      'http no dot' => [
        'example.com',
        80,
        '',
        '',
        TRUE,
        'http://exampleXcom/page',
        'http://exampleXcom/page',
      ],
      'http files on different port than the web request' => [
        'example.com',
        80,
        '',
        '',
        TRUE,
        'http://example.com:9000/page',
        'http://example.com:9000/page',
      ],
      'https files on different port than the web request' => [
        'example.com',
        443,
        'on',
        '',
        TRUE,
        'https://example.com:8443/page',
        'https://example.com:8443/page',
      ],
      'http files on different port than the web request on non default port' => [
        'example.com',
        8080,
        '',
        '',
        TRUE,
        'http://example.com:9000/page',
        'http://example.com:9000/page',
      ],
      'https files on different port than the web request on non default port' => [
        'example.com',
        8443,
        'on',
        '',
        TRUE,
        'https://example.com:9000/page',
        'https://example.com:9000/page',
      ],
      'http with default port explicit mentioned in URL' => [
        'example.com',
        80,
        '',
        '',
        TRUE,
        'http://example.com:80/page',
        '/page',
      ],
      'https with default port explicit mentioned in URL' => [
        'example.com',
        443,
        'on',
        '',
        TRUE,
        'https://example.com:443/page',
        '/page',
      ],
    ];
    return $data;
  }

}
