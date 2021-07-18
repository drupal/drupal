<?php

namespace Drupal\Tests\system\Kernel\File;

use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests system stream wrapper functions.
 *
 * @group File
 */
class ExtensionStreamTest extends KernelTestBase {

  /**
   * A list of extension stream wrappers keyed by scheme.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperInterface[]
   */
  protected $streamWrappers = [];

  /**
   * The base url for the current request.
   *
   * @var string
   */
  protected $baseUrl;

  /**
   * The list of modules to enable.
   *
   * @var string[]
   */
  protected static $modules = ['file_module_test', 'system'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Find the base url to be used later in tests.
    $this->baseUrl = $this->container->get('request_stack')->getCurrentRequest()->getUriForPath(base_path());

    /** @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager */
    $stream_wrapper_manager = $this->container->get('stream_wrapper_manager');

    // Get stream wrapper instances.
    foreach (['module', 'theme', 'profile'] as $scheme) {
      $this->streamWrappers[$scheme] = $stream_wrapper_manager->getViaScheme($scheme);
    }

    /** @var \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer */
    $theme_installer = $this->container->get('theme_installer');
    // Install Bartik and Seven themes.
    $theme_installer->install(['bartik', 'seven']);

    // Set 'minimal' as installed profile for the purposes of this test.
    $this->setInstallProfile('minimal');
    $this->enableModules(['minimal']);
  }

  /**
   * Tests invalid stream uris.
   *
   * @param string $uri
   *   The URI being tested.
   *
   * @dataProvider providerInvalidUris
   */
  public function testInvalidStreamUri(string $uri): void {
    $message = "\\InvalidArgumentException thrown on invalid uri $uri.";
    try {
      $this->streamWrappers['module']->dirname($uri);
      $this->fail($message);
    }
    catch (\InvalidArgumentException $e) {
      $this->assertSame($e->getMessage(), "Malformed uri parameter passed: $uri", $message);
    }
  }

  /**
   * Provides test cases for testInvalidStreamUri()
   *
   * @return array[]
   *   A list of urls to test.
   */
  public function providerInvalidUris(): array {
    return [
      ['invalid/uri'],
      ['invalid_uri'],
      ['module/invalid/uri'],
      ['module/invalid_uri'],
      ['module:invalid_uri'],
      ['module::/invalid/uri'],
      ['module::/invalid_uri'],
      ['module//:invalid/uri'],
      ['module//invalid_uri'],
      ['module//invalid/uri'],
    ];
  }

  /**
   * Tests call of ::dirname() without setting a URI first.
   */
  public function testDirnameAsParameter(): void {
    $this->assertEquals('module://system', $this->streamWrappers['module']->dirname('module://system/system.admin.css'));
  }

  /**
   * Tests the extension stream wrapper methods.
   *
   * @param string $uri
   *   The uri to be tested.
   * @param string $dirname
   *   The expectation for dirname() method.
   * @param string $realpath
   *   The expectation for realpath() method.
   * @param string $getExternalUrl
   *   The expectation for getExternalUrl() method.
   *
   * @dataProvider providerStreamWrapperMethods
   */
  public function testStreamWrapperMethods(string $uri, string $dirname, string $realpath, string $getExternalUrl): void {
    $this->enableModules(['image']);

    // Prefix realpath() expected value with Drupal root directory.
    $realpath = DRUPAL_ROOT . $realpath;
    // Prefix getExternalUrl() expected value with base url.
    $getExternalUrl = "{$this->baseUrl}$getExternalUrl";
    $case = compact('dirname', 'realpath', 'getExternalUrl');

    foreach ($case as $method => $expected) {
      [$scheme] = explode('://', $uri);
      $this->streamWrappers[$scheme]->setUri($uri);
      $this->assertSame($expected, $this->streamWrappers[$scheme]->$method());
    }
  }

  /**
   * Provides test cases for testStreamWrapperMethods().
   *
   * @return array[]
   *   A list of test cases. Each case consists of the following items:
   *   - The uri to be tested.
   *   - The result or the exception when running dirname() method.
   *   - The result or the exception when running realpath() method. The value
   *     is prefixed later, in the test method, with the Drupal root directory.
   *   - The result or the exception when running getExternalUrl() method. The
   *     value is prefixed later, in the test method, with the base url.
   */
  public function providerStreamWrapperMethods(): array {
    return [
      // Cases for module:// stream wrapper.
      [
        'module://system',
        'module://system',
        '/core/modules/system',
        'core/modules/system',
      ],
      [
        'module://system/css/system.admin.css',
        'module://system/css',
        '/core/modules/system/css/system.admin.css',
        'core/modules/system/css/system.admin.css',
      ],
      [
        'module://file_module_test/file_module_test.dummy.inc',
        'module://file_module_test',
        '/core/modules/file/tests/file_module_test/file_module_test.dummy.inc',
        'core/modules/file/tests/file_module_test/file_module_test.dummy.inc',
      ],
      [
        'module://image/sample.png',
        'module://image',
        '/core/modules/image/sample.png',
        'core/modules/image/sample.png',
      ],
      // Cases for theme:// stream wrapper.
      [
        'theme://seven',
        'theme://seven',
        '/core/themes/seven',
        'core/themes/seven',
      ],
      [
        'theme://seven/style.css',
        'theme://seven',
        '/core/themes/seven/style.css',
        'core/themes/seven/style.css',
      ],
      [
        'theme://bartik/color/preview.js',
        'theme://bartik/color',
        '/core/themes/bartik/color/preview.js',
        'core/themes/bartik/color/preview.js',
      ],
      // Cases for profile:// stream wrapper.
      [
        'profile://',
        'profile://',
        '/core/profiles/minimal',
        'core/profiles/minimal',
      ],
      [
        'profile://config/install/block.block.stark_login.yml',
        'profile://config/install',
        '/core/profiles/minimal/config/install/block.block.stark_login.yml',
        'core/profiles/minimal/config/install/block.block.stark_login.yml',
      ],
      [
        'profile://config/install/node.type.article.yml',
        'profile://config/install',
        '/core/profiles/minimal/config/install/node.type.article.yml',
        'core/profiles/minimal/config/install/node.type.article.yml',
      ],
      [
        'profile://minimal.info.yml',
        'profile://',
        '/core/profiles/minimal/minimal.info.yml',
        'core/profiles/minimal/minimal.info.yml',
      ],
    ];
  }

  /**
   * Test the dirname method on uninstalled extensions.
   *
   * @param string $uri
   *   The uri to be tested.
   * @param string $class_name
   *   The class name of the expected exception.
   * @param string $expected_message
   *   The The expected exception message.
   *
   * @dataProvider providerStreamWrapperMethodsOnMissingExtensions
   */
  public function testStreamWrapperDirnameOnMissingExtensions(string $uri, string $class_name, string $expected_message): void {
    [$scheme] = explode('://', $uri);
    $this->streamWrappers[$scheme]->setUri($uri);

    $this->expectException($class_name);
    $this->expectExceptionMessage($expected_message);
    $this->streamWrappers[$scheme]->dirname();
  }

  /**
   * Test the realpath method on uninstalled extensions.
   *
   * @param string $uri
   *   The uri to be tested.
   * @param string $class_name
   *   The class name of the expected exception.
   * @param string $expected_message
   *   The The expected exception message.
   *
   * @dataProvider providerStreamWrapperMethodsOnMissingExtensions
   */
  public function testStreamWrapperRealpathOnMissingExtensions(string $uri, string $class_name, string $expected_message): void {
    [$scheme] = explode('://', $uri);
    $this->streamWrappers[$scheme]->setUri($uri);

    $this->expectException($class_name);
    $this->expectExceptionMessage($expected_message);
    $this->streamWrappers[$scheme]->realpath();
  }

  /**
   * Test the getExternalUrl method on uninstalled extensions.
   *
   * @param string $uri
   *   The uri to be tested.
   * @param string $class_name
   *   The class name of the expected exception.
   * @param string $expected_message
   *   The The expected exception message.
   *
   * @dataProvider providerStreamWrapperMethodsOnMissingExtensions
   */
  public function testStreamWrapperGetExternalUrlOnMissingExtensions(string $uri, string $class_name, string $expected_message): void {
    [$scheme] = explode('://', $uri);
    $this->streamWrappers[$scheme]->setUri($uri);

    $this->expectException($class_name);
    $this->expectExceptionMessage($expected_message);
    $this->streamWrappers[$scheme]->getExternalUrl();
  }

  /**
   * Test cases for testing stream wrapper methods on missing extensions.
   *
   * @return array[]
   *   A list of test cases. Each case consists of the following items:
   *   - The uri to be tested.
   *   - The class name of the expected exception.
   *   - The expected exception message.
   */
  public function providerStreamWrapperMethodsOnMissingExtensions(): array {
    return [
      // Cases for module:// stream wrapper.
      [
        'module://ckeditor/ckeditor.info.yml',
        UnknownExtensionException::class,
        'The module ckeditor does not exist.',
      ],
      [
        'module://foo_bar/foo.bar.js',
        UnknownExtensionException::class,
        'The module foo_bar does not exist.',
      ],
      [
        'module://image/sample.png',
        UnknownExtensionException::class,
        'The module image does not exist.',
      ],
      // Cases for theme:// stream wrapper.
      [
        'theme://fifteen/screenshot.png',
        UnknownExtensionException::class,
        'The theme fifteen does not exist.',
      ],
      [
        'theme://stark/stark.info.yml',
        UnknownExtensionException::class,
        'The theme stark does not exist.',
      ],
    ];
  }

}
