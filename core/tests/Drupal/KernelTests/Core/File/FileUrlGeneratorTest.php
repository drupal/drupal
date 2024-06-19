<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\File;

use Drupal\Core\File\Exception\InvalidStreamWrapperException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * @coversDefaultClass \Drupal\Core\File\FileUrlGenerator
 *
 * @group File
 */
class FileUrlGeneratorTest extends FileTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'file_test'];

  /**
   * The file URL generator under test.
   *
   * @var \Drupal\Core\File\FileUrlGenerator
   */
  protected $fileUrlGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->fileUrlGenerator = $this->container->get('file_url_generator');
  }

  /**
   * Tests missing stream handler.
   *
   * @covers ::generate
   */
  public function testGenerateMissingStreamWrapper(): void {
    $this->expectException(InvalidStreamWrapperException::class);
    $result = $this->fileUrlGenerator->generate("foo://bar");
  }

  /**
   * Tests missing stream handler.
   *
   * @covers ::generateString
   */
  public function testGenerateStringMissingStreamWrapper(): void {
    $this->expectException(InvalidStreamWrapperException::class);
    $result = $this->fileUrlGenerator->generateString("foo://bar");
  }

  /**
   * Tests missing stream handler.
   *
   * @covers ::generateAbsoluteString
   */
  public function testGenerateAbsoluteStringMissingStreamWrapper(): void {
    $this->expectException(InvalidStreamWrapperException::class);
    $result = $this->fileUrlGenerator->generateAbsoluteString("foo://bar");
  }

  /**
   * Tests the rewriting of shipped file URLs by hook_file_url_alter().
   *
   * @covers ::generateAbsoluteString
   */
  public function testShippedFileURL(): void {
    // Test generating a URL to a shipped file (i.e. a file that is part of
    // Drupal core, a module or a theme, for example a JavaScript file).

    // Test alteration of file URLs to use a CDN.
    \Drupal::state()->set('file_test.hook_file_url_alter', 'cdn');
    $filepath = 'core/assets/vendor/jquery/jquery.min.js';
    $url = $this->fileUrlGenerator->generateAbsoluteString($filepath);
    $this->assertEquals(FILE_URL_TEST_CDN_1 . '/' . $filepath, $url, 'Correctly generated a CDN URL for a shipped file.');
    $filepath = 'core/misc/favicon.ico';
    $url = $this->fileUrlGenerator->generateAbsoluteString($filepath);
    $this->assertEquals(FILE_URL_TEST_CDN_2 . '/' . $filepath, $url, 'Correctly generated a CDN URL for a shipped file.');

    // Test alteration of file URLs to use root-relative URLs.
    \Drupal::state()->set('file_test.hook_file_url_alter', 'root-relative');
    $filepath = 'core/assets/vendor/jquery/jquery.min.js';
    $url = $this->fileUrlGenerator->generateAbsoluteString($filepath);
    $this->assertEquals(base_path() . '/' . $filepath, $url, 'Correctly generated a root-relative URL for a shipped file.');
    $filepath = 'core/misc/favicon.ico';
    $url = $this->fileUrlGenerator->generateAbsoluteString($filepath);
    $this->assertEquals(base_path() . '/' . $filepath, $url, 'Correctly generated a root-relative URL for a shipped file.');

    // Test alteration of file URLs to use protocol-relative URLs.
    \Drupal::state()->set('file_test.hook_file_url_alter', 'protocol-relative');
    $filepath = 'core/assets/vendor/jquery/jquery.min.js';
    $url = $this->fileUrlGenerator->generateAbsoluteString($filepath);
    $this->assertEquals('/' . base_path() . '/' . $filepath, $url, 'Correctly generated a protocol-relative URL for a shipped file.');
    $filepath = 'core/misc/favicon.ico';
    $url = $this->fileUrlGenerator->generateAbsoluteString($filepath);
    $this->assertEquals('/' . base_path() . '/' . $filepath, $url, 'Correctly generated a protocol-relative URL for a shipped file.');

    // Test alteration of file URLs with query strings and/or fragment.
    \Drupal::state()->delete('file_test.hook_file_url_alter');
    $filepath = 'core/misc/favicon.ico';
    $url = $this->fileUrlGenerator->generateAbsoluteString($filepath . '?foo');
    $this->assertEquals($GLOBALS['base_url'] . '/' . $filepath . '?foo=', $url, 'Correctly generated URL. The query string is present.');
    $url = $this->fileUrlGenerator->generateAbsoluteString($filepath . '?foo=bar');
    $this->assertEquals($GLOBALS['base_url'] . '/' . $filepath . '?foo=bar', $url, 'Correctly generated URL. The query string is present.');
    $url = $this->fileUrlGenerator->generateAbsoluteString($filepath . '#v1.2');
    $this->assertEquals($GLOBALS['base_url'] . '/' . $filepath . '#v1.2', $url, 'Correctly generated URL. The fragment is present.');
    $url = $this->fileUrlGenerator->generateAbsoluteString($filepath . '?foo=bar#v1.2');
    $this->assertEquals($GLOBALS['base_url'] . '/' . $filepath . '?foo=bar#v1.2', $url, 'Correctly generated URL. The query string amd fragment is present.');
  }

  /**
   * Tests the rewriting of public managed file URLs by hook_file_url_alter().
   *
   * @covers ::generateAbsoluteString
   */
  public function testPublicManagedFileURL(): void {
    // Test generating a URL to a managed file.

    // Test alteration of file URLs to use a CDN.
    \Drupal::state()->set('file_test.hook_file_url_alter', 'cdn');
    $uri = $this->createUri();
    $url = $this->fileUrlGenerator->generateAbsoluteString($uri);
    $public_directory_path = \Drupal::service('stream_wrapper_manager')
      ->getViaScheme('public')
      ->getDirectoryPath();
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $this->assertEquals(FILE_URL_TEST_CDN_2 . '/' . $public_directory_path . '/' . $file_system->basename($uri), $url, 'Correctly generated a CDN URL for a created file.');

    // Test alteration of file URLs to use root-relative URLs.
    \Drupal::state()->set('file_test.hook_file_url_alter', 'root-relative');
    $uri = $this->createUri();
    $url = $this->fileUrlGenerator->generateAbsoluteString($uri);
    $this->assertEquals(base_path() . '/' . $public_directory_path . '/' . $file_system->basename($uri), $url, 'Correctly generated a root-relative URL for a created file.');

    // Test alteration of file URLs to use a protocol-relative URLs.
    \Drupal::state()->set('file_test.hook_file_url_alter', 'protocol-relative');
    $uri = $this->createUri();
    $url = $this->fileUrlGenerator->generateAbsoluteString($uri);
    $this->assertEquals('/' . base_path() . '/' . $public_directory_path . '/' . $file_system->basename($uri), $url, 'Correctly generated a protocol-relative URL for a created file.');
  }

  /**
   * Tests generate absolute string with relative URL.
   *
   * @covers ::generateAbsoluteString
   */
  public function testRelativeFileURL(): void {
    // Disable file_test.module's hook_file_url_alter() implementation.
    \Drupal::state()->set('file_test.hook_file_url_alter', NULL);

    // Create a mock Request for transformRelative().
    $request = Request::create($GLOBALS['base_url']);
    $request->setSession(new Session(new MockArraySessionStorage()));
    $this->container->get('request_stack')->push($request);
    \Drupal::setContainer($this->container);

    // Shipped file.
    $filepath = 'core/assets/vendor/jquery/jquery.min.js';
    $url = $this->fileUrlGenerator->generateAbsoluteString($filepath);
    $this->assertSame(base_path() . $filepath, $this->fileUrlGenerator->transformRelative($url));

    // Managed file.
    $uri = $this->createUri();
    $url = $this->fileUrlGenerator->generateAbsoluteString($uri);
    $public_directory_path = \Drupal::service('stream_wrapper_manager')
      ->getViaScheme('public')
      ->getDirectoryPath();
    $this->assertSame(base_path() . $public_directory_path . '/' . rawurlencode(\Drupal::service('file_system')
      ->basename($uri)), $this->fileUrlGenerator->transformRelative($url));
  }

  /**
   * @covers ::generate
   *
   * @dataProvider providerGenerateURI
   */
  public function testGenerateURI($filepath, $expected): void {
    // Disable file_test.module's hook_file_url_alter() implementation.
    \Drupal::state()->set('file_test.hook_file_url_alter', NULL);

    // Create a mock Request for transformRelative().
    $request = Request::create($GLOBALS['base_url']);
    $request->setSession(new Session(new MockArraySessionStorage()));
    $this->container->get('request_stack')->push($request);
    \Drupal::setContainer($this->container);

    // No schema file.
    $url = $this->fileUrlGenerator->generate($filepath);
    $this->assertEquals($expected, $url->toUriString());
  }

  /**
   * @covers ::generate
   */
  public function testGenerateURIWithSchema(): void {
    // Disable file_test.module's hook_file_url_alter() implementation.
    \Drupal::state()->set('file_test.hook_file_url_alter', NULL);

    // Create a mock Request for transformRelative().
    $request = Request::create($GLOBALS['base_url']);
    $request->setSession(new Session(new MockArraySessionStorage()));
    $this->container->get('request_stack')->push($request);
    \Drupal::setContainer($this->container);

    $public_directory_path = \Drupal::service('stream_wrapper_manager')
      ->getViaScheme('public')
      ->getDirectoryPath();

    $url = $this->fileUrlGenerator->generate('public://path/to/file.png');
    $this->assertEquals('base:/' . $public_directory_path . '/path/to/file.png', $url->getUri());
  }

  /**
   * Data provider.
   */
  public static function providerGenerateURI() {
    return [
      'schemaless' =>
        [
          '//core/assets/vendor/jquery/jquery.min.js',
          '//core/assets/vendor/jquery/jquery.min.js',
        ],
      'query string' =>
        [
          '//core/assets/vendor/jquery/jquery.min.js?foo',
          '//core/assets/vendor/jquery/jquery.min.js?foo',
        ],
      'query string and hashes' =>
        [
          '//core/assets/vendor/jquery/jquery.min.js?foo=bar#whizz',
          '//core/assets/vendor/jquery/jquery.min.js?foo=bar#whizz',
        ],
      'hashes' =>
        [
          '//core/assets/vendor/jquery/jquery.min.js#whizz',
          '//core/assets/vendor/jquery/jquery.min.js#whizz',
        ],
      'root-relative' =>
        [
          '/core/assets/vendor/jquery/jquery.min.js',
          'base:/core/assets/vendor/jquery/jquery.min.js',
        ],
      'relative' =>
        [
          'core/assets/vendor/jquery/jquery.min.js',
          'base:core/assets/vendor/jquery/jquery.min.js',
        ],
      'external' =>
        [
          'https://www.example.com/core/assets/vendor/jquery/jquery.min.js',
          'https://www.example.com/core/assets/vendor/jquery/jquery.min.js',
        ],
      'external stream wrapper' =>
        [
          'dummy-external-readonly://core/assets/vendor/jquery/jquery.min.js',
          'https://www.dummy-external-readonly.com/core/assets/vendor/jquery/jquery.min.js',
        ],
      'external stream wrapper with query string' =>
        [
          'dummy-external-readonly://core/assets/vendor/jquery/jquery.min.js?foo=bar',
          'https://www.dummy-external-readonly.com/core/assets/vendor/jquery/jquery.min.js?foo=bar',
        ],
      'external stream wrapper with hashes' =>
        [
          'dummy-external-readonly://core/assets/vendor/jquery/jquery.min.js#whizz',
          'https://www.dummy-external-readonly.com/core/assets/vendor/jquery/jquery.min.js#whizz',
        ],
      'external stream wrapper with query string and hashes' =>
        [
          'dummy-external-readonly://core/assets/vendor/jquery/jquery.min.js?foo=bar#whizz',
          'https://www.dummy-external-readonly.com/core/assets/vendor/jquery/jquery.min.js?foo=bar#whizz',
        ],
    ];
  }

}
