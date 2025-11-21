<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\StreamWrapper;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\StreamWrapper\ExtensionStreamBase;
use Drupal\Core\StreamWrapper\ModuleStream;
use Drupal\Core\StreamWrapper\ThemeStream;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Tests extension stream wrappers.
 */
#[Group('File')]
#[RunTestsInSeparateProcesses]
#[CoversClass(ModuleStream::class)]
#[CoversClass(ThemeStream::class)]
#[CoversClass(ExtensionStreamBase::class)]
class ExtensionStreamTest extends KernelTestBase {

  /**
   * A list of extension stream wrappers keyed by scheme.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperInterface[]
   */
  protected array $streamWrappers = [];

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

    foreach (['module', 'theme'] as $scheme) {
      $this->streamWrappers[$scheme] = $this->container->get("stream_wrapper.$scheme");
    }

    $this->container->get('theme_installer')->install(['olivero', 'claro']);
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);

    // By default, allow several types of file extensions in this test.
    $allowed = ['css', 'js', 'png', 'yml'];
    $container->setParameter('stream_wrapper.allowed_file_extensions', [
      'module' => $allowed,
      'theme' => $allowed,
    ]);
  }

  /**
   * Tests invalid stream URIs.
   *
   * @param string $uri
   *   The URI being tested.
   */
  #[TestWith(['invalid/uri'])]
  #[TestWith(['invalid_uri'])]
  #[TestWith(['module/invalid/uri'])]
  #[TestWith(['module/invalid_uri'])]
  #[TestWith(['module:invalid_uri'])]
  #[TestWith(['module::/invalid/uri'])]
  #[TestWith(['module::/invalid_uri'])]
  #[TestWith(['module//:invalid/uri'])]
  #[TestWith(['module//invalid_uri'])]
  #[TestWith(['module//invalid/uri'])]
  #[TestWith([''])]
  public function testInvalidStreamUri(string $uri): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("Malformed extension URI: {$uri}");
    $this->streamWrappers['module']->dirname($uri);
  }

  /**
   * Tests call of ::dirname() without setting a URI first.
   */
  public function testDirnameAsParameter(): void {
    $this->assertSame('module://system', $this->streamWrappers['module']->dirname('module://system/system.admin.css'));
  }

  /**
   * Tests the extension stream wrapper methods.
   *
   * @param string $uri
   *   The uri to be tested.
   * @param string $expected_dirname
   *   Expected result of calling the dirname() method.
   * @param string $expected_path
   *   Path added to the base URL and extension's directory to test the
   *   realpath() and getExternalUrl() methods.
   */
  #[TestWith([
    'module://system/css/system.admin.css',
    'module://system/css',
    'core/modules/system/css/system.admin.css',
  ])]
  #[TestWith([
    'module://image/sample.png',
    'module://image',
    'core/modules/image/sample.png',
  ])]
  #[TestWith([
    'theme://claro/style.css',
    'theme://claro',
    'core/themes/claro/style.css',
  ])]
  #[TestWith([
    'theme://olivero/js/checkbox.js',
    'theme://olivero/js',
    'core/themes/olivero/js/checkbox.js',
  ])]
  public function testStreamWrapperMethods(string $uri, string $expected_dirname, string $expected_path): void {
    $base_url = $this->container->get('router.request_context')->getCompleteBaseUrl();
    $this->enableModules(['image']);

    [$scheme] = explode('://', $uri);
    $this->streamWrappers[$scheme]->setUri($uri);

    $this->assertSame($expected_dirname, $this->streamWrappers[$scheme]->dirname());
    $this->assertSame($this->root . '/' . $expected_path, $this->streamWrappers[$scheme]->realpath());
    $this->assertSame($base_url . '/' . $expected_path, $this->streamWrappers[$scheme]->getExternalUrl());
  }

  /**
   * Test the dirname method on uninstalled extensions.
   *
   * @param string $uri
   *   The URI to be tested.
   * @param string $expected_message
   *   The expected exception message.
   */
  #[DataProvider('providerStreamWrapperMethodsOnMissingExtensions')]
  public function testStreamWrapperDirnameOnMissingExtensions(string $uri, string $expected_message): void {
    [$scheme] = explode('://', $uri);
    $this->streamWrappers[$scheme]->setUri($uri);

    $this->expectException(UnknownExtensionException::class);
    $this->expectExceptionMessage($expected_message);
    $this->streamWrappers[$scheme]->dirname();
  }

  /**
   * Test the realpath method on uninstalled extensions.
   *
   * @param string $uri
   *   The URI to be tested.
   * @param string $expected_message
   *   The expected exception message.
   */
  #[DataProvider('providerStreamWrapperMethodsOnMissingExtensions')]
  public function testStreamWrapperRealpathOnMissingExtensions(string $uri, string $expected_message): void {
    [$scheme] = explode('://', $uri);
    $this->streamWrappers[$scheme]->setUri($uri);

    $this->expectException(UnknownExtensionException::class);
    $this->expectExceptionMessage($expected_message);
    $this->streamWrappers[$scheme]->realpath();
  }

  /**
   * Test the getExternalUrl method on uninstalled extensions.
   *
   * @param string $uri
   *   The URI to be tested.
   * @param string $expected_message
   *   The expected exception message.
   */
  #[DataProvider('providerStreamWrapperMethodsOnMissingExtensions')]
  public function testStreamWrapperGetExternalUrlOnMissingExtensions(string $uri, string $expected_message): void {
    [$scheme] = explode('://', $uri);
    $this->streamWrappers[$scheme]->setUri($uri);

    $this->expectException(UnknownExtensionException::class);
    $this->expectExceptionMessage($expected_message);
    $this->streamWrappers[$scheme]->getExternalUrl();
  }

  /**
   * Test cases for testing stream wrapper methods on missing extensions.
   *
   * @return array[]
   *   A list of test cases. Each case consists of the following items:
   *   - The URI to be tested.
   *   - The expected exception message.
   */
  public static function providerStreamWrapperMethodsOnMissingExtensions(): array {
    return [
      // Cases for the module:// stream wrapper.
      'Module is not installed' => [
        'module://field/field.info.yml',
        'The module field does not exist.',
      ],
      'Module does not exist' => [
        'module://foo_bar/foo.bar.js',
        'The module foo_bar does not exist.',
      ],
      'Theme is not installed' => [
        'theme://stark/screenshot.png',
        'The theme stark does not exist.',
      ],
      'Theme does not exist' => [
        'theme://foo/foo.info.yml',
        'The theme foo does not exist.',
      ],
      'Theme streamwrapper cannot access installed module' => [
        'theme://system/system.info.yml',
        'The theme system does not exist.',
      ],
      'Module stream wrapper cannot access installed theme' => [
        'module://claro/claro.info.yml',
        'The module claro does not exist.',
      ],
    ];
  }

  /**
   * Tests stream wrappers after module uninstall.
   */
  public function testWrappersAfterModuleUninstall(): void {
    $this->assertSame('module://file_module_test', $this->streamWrappers['module']->dirname('module://file_module_test/file_module_test.info.yml'));
    $this->container->get('module_installer')->uninstall(['file_module_test']);
    $this->expectException(UnknownExtensionException::class);
    $this->expectExceptionMessage('The module file_module_test does not exist.');
    $this->streamWrappers['module']->dirname('module://file_module_test/file_module_test.info.yml');
  }

  /**
   * Tests stream wrappers after theme uninstall.
   */
  public function testWrappersAfterThemeUninstall(): void {
    $this->assertSame('theme://claro', $this->streamWrappers['theme']->dirname('theme://claro/claro.info.yml'));
    $this->container->get('theme_installer')->uninstall(['claro']);
    $this->expectException(UnknownExtensionException::class);
    $this->expectExceptionMessage('The theme claro does not exist.');
    $this->streamWrappers['theme']->dirname('theme://claro/claro.info.yml');
  }

  /**
   * Tests path traversal.
   */
  #[TestWith(['file_module_test.info.yml', TRUE, TRUE])]
  #[TestWith(['src/../file_module_test.info.yml', TRUE, TRUE])]
  #[TestWith(['/./file_module_test.info.yml', TRUE, TRUE])]
  #[TestWith(['file_module_test.does_not_exist.yml', FALSE, FALSE])]
  #[TestWith(['src/../file_module_test.does_not_exist.yml', FALSE, FALSE])]
  #[TestWith(['src/../../../file.info.yml', FALSE, TRUE])]
  public function testPathTraversal(string $path, bool $stream_wrapper_exists, bool $file_exists): void {
    $stream_assert = $stream_wrapper_exists ? 'assertFileExists' : 'assertFileDoesNotExist';
    $file_assert = $file_exists ? 'assertFileExists' : 'assertFileDoesNotExist';

    $this->$stream_assert('module://file_module_test/' . $path);
    $this->$file_assert($this->container->get('module_handler')->getModule('file_module_test')->getPath() . "/$path");
  }

  /**
   * Tests that certain file extensions are disallowed by default.
   */
  #[TestWith(['module://system'])]
  #[TestWith(['module://system/system.module'])]
  #[TestWith(['theme://claro'])]
  #[TestWith(['theme://claro/claro.theme'])]
  public function testDisallowedFileExtensions(string $uri): void {
    [$scheme] = explode('://', $uri);
    $extension = pathinfo($uri, PATHINFO_EXTENSION);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The $scheme stream wrapper does not support the '$extension' file type.");
    $this->streamWrappers[$scheme]->setUri($uri);
    $this->streamWrappers[$scheme]->realpath();
  }

  /**
   * Tests that scheme with no extension throws an exception.
   */
  #[TestWith(['module'])]
  #[TestWith(['theme'])]
  public function testNoExtensionError(string $scheme): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Unable to determine the extension name.');
    file_exists($scheme . '://');
  }

}
