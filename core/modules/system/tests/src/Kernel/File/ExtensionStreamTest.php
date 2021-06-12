<?php

namespace Drupal\Tests\system\Kernel\File;

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
  }

  /**
   * Tests invalid stream uris.
   *
   * @param string $uri
   *   The URI being tested.
   *
   * @dataProvider providerInvalidUris
   */
  public function testInvalidStreamUri($uri) {
    // Set 'minimal' as installed profile for the purposes of this test.
    $this->setInstallProfile('minimal');
    $this->enableModules(['minimal']);

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
  public function providerInvalidUris() {
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
  public function testDirnameAsParameter() {
    // Set 'minimal' as installed profile for the purposes of this test.
    $this->setInstallProfile('minimal');
    $this->enableModules(['minimal']);

    $this->assertEquals('module://system', $this->streamWrappers['module']->dirname('module://system/system.admin.css'));
  }

  /**
   * Test the extension stream wrapper methods.
   *
   * @param string $uri
   *   The uri to be tested.
   * @param string|\RuntimeException|\InvalidArgumentException $dirname
   *   The expectation for dirname() method.
   * @param string|\RuntimeException|\InvalidArgumentException $realpath
   *   The expectation for realpath() method.
   * @param string|\RuntimeException|\InvalidArgumentException $getExternalUrl
   *   The expectation for getExternalUrl() method.
   *
   * @dataProvider providerStreamWrapperMethods
   */
  public function testStreamWrapperMethods($uri, $dirname, $realpath, $getExternalUrl) {
    // Set 'minimal' as installed profile for the purposes of this test.
    $this->setInstallProfile('minimal');
    $this->enableModules(['minimal']);

    // Prefix realpath() expected value with Drupal root directory.
    $realpath = is_string($realpath) ? DRUPAL_ROOT . $realpath : $realpath;
    // Prefix getExternalUrl() expected value with base url.
    $getExternalUrl = is_string($getExternalUrl) ? "{$this->baseUrl}$getExternalUrl" : $getExternalUrl;
    $case = compact('dirname', 'realpath', 'getExternalUrl');

    foreach ($case as $method => $expected) {
      list($scheme,) = explode('://', $uri);
      $this->streamWrappers[$scheme]->setUri($uri);
      if ($expected instanceof \InvalidArgumentException || $expected instanceof \RuntimeException) {
        /** @var \Exception $expected */
        $message = sprintf('Exception thrown: %s("%s").', get_class($expected), $expected->getMessage());
        try {
          $this->streamWrappers[$scheme]->$method();
          $this->fail($message);
        }
        catch (\InvalidArgumentException $e) {
          $this->assertSame($expected->getMessage(), $e->getMessage(), $message);
        }
        catch (\RuntimeException $e) {
          $this->assertSame($expected->getMessage(), $e->getMessage(), $message);
        }
      }
      elseif (is_string($expected)) {
        $this->assertSame($expected, $this->streamWrappers[$scheme]->$method());
      }
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
  public function providerStreamWrapperMethods() {
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
        'module://file_module_test/src/file_module_test.dummy.inc',
        'module://file_module_test/src',
        '/core/modules/file/tests/file_module_test/src/file_module_test.dummy.inc',
        'core/modules/file/tests/file_module_test/src/file_module_test.dummy.inc',
      ],
      [
        'module://ckeditor/ckeditor.info.yml',
        new \RuntimeException('Module ckeditor does not exist or is not installed'),
        new \RuntimeException('Module ckeditor does not exist or is not installed'),
        new \RuntimeException('Module ckeditor does not exist or is not installed'),
      ],
      [
        'module://foo_bar/foo.bar.js',
        new \RuntimeException('Module foo_bar does not exist or is not installed'),
        new \RuntimeException('Module foo_bar does not exist or is not installed'),
        new \RuntimeException('Module foo_bar does not exist or is not installed'),
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
      [
        'theme://fifteen/screenshot.png',
        new \RuntimeException('Theme fifteen does not exist or is not installed'),
        new \RuntimeException('Theme fifteen does not exist or is not installed'),
        new \RuntimeException('Theme fifteen does not exist or is not installed'),
      ],
      [
        'theme://stark/stark.info.yml',
        new \RuntimeException('Theme stark does not exist or is not installed'),
        new \RuntimeException('Theme stark does not exist or is not installed'),
        new \RuntimeException('Theme stark does not exist or is not installed'),
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

}
