<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Extension;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Extension\ExtensionList;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\State\StateInterface;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\Core\Extension\ExtensionList
 * @group Extension
 */
class ExtensionListTest extends UnitTestCase {

  /**
   * @covers ::getName
   */
  public function testGetNameWithNonExistingExtension(): void {
    [$cache, $info_parser, $module_handler, $state] = $this->getMocks();
    $test_extension_list = new TestExtension($this->randomMachineName(), 'test_extension', $cache->reveal(), $info_parser->reveal(), $module_handler->reveal(), $state->reveal(), 'testing');

    $extension_discovery = $this->prophesize(ExtensionDiscovery::class);
    $extension_discovery->scan('test_extension')->willReturn([]);
    $test_extension_list->setExtensionDiscovery($extension_discovery->reveal());

    $this->expectException(UnknownExtensionException::class);
    $test_extension_list->getName('test_name');
  }

  /**
   * @covers ::getName
   */
  public function testGetName(): void {
    $test_extension_list = $this->setupTestExtensionList();

    $this->assertEquals('test name', $test_extension_list->getName('test_name'));
  }

  /**
   * @covers ::get
   */
  public function testGetWithNonExistingExtension(): void {
    [$cache, $info_parser, $module_handler, $state] = $this->getMocks();
    $test_extension_list = new TestExtension($this->randomMachineName(), 'test_extension', $cache->reveal(), $info_parser->reveal(), $module_handler->reveal(), $state->reveal(), 'testing');

    $extension_discovery = $this->prophesize(ExtensionDiscovery::class);
    $extension_discovery->scan('test_extension')->willReturn([]);
    $test_extension_list->setExtensionDiscovery($extension_discovery->reveal());

    $this->expectException(UnknownExtensionException::class);
    $test_extension_list->get('test_name');
  }

  /**
   * @covers ::get
   */
  public function testGet(): void {
    $test_extension_list = $this->setupTestExtensionList();

    $extension = $test_extension_list->get('test_name');
    $this->assertInstanceOf(Extension::class, $extension);
    $this->assertEquals('test_name', $extension->getName());
  }

  /**
   * @covers ::getList
   */
  public function testGetList(): void {
    $test_extension_list = $this->setupTestExtensionList();

    $extensions = $test_extension_list->getList();
    $this->assertCount(1, $extensions);
    $this->assertEquals('test_name', $extensions['test_name']->getName());
  }

  /**
   * @covers ::getExtensionInfo
   * @covers ::getAllInstalledInfo
   */
  public function testGetExtensionInfo(): void {
    $test_extension_list = $this->setupTestExtensionList();
    $test_extension_list->setInstalledExtensions(['test_name']);

    $info = $test_extension_list->getExtensionInfo('test_name');
    $this->assertEquals([
      'type' => 'test_extension',
      'core' => '8.x',
      'name' => 'test name',
      'mtime' => 123456789,
    ], $info);
  }

  /**
   * @covers ::getAllAvailableInfo
   */
  public function testGetAllAvailableInfo(): void {
    $test_extension_list = $this->setupTestExtensionList();

    $infos = $test_extension_list->getAllAvailableInfo();
    $this->assertEquals([
      'test_name' => [
        'type' => 'test_extension',
        'core' => '8.x',
        'name' => 'test name',
        'mtime' => 123456789,
      ],
    ], $infos);
  }

  /**
   * @covers ::getAllInstalledInfo
   */
  public function testGetAllInstalledInfo(): void {
    $test_extension_list = $this->setupTestExtensionList(['test_name', 'test_name_2']);
    $test_extension_list->setInstalledExtensions(['test_name_2']);

    $infos = $test_extension_list->getAllInstalledInfo();
    $this->assertEquals([
      'test_name_2' => [
        'type' => 'test_extension',
        'core' => '8.x',
        'name' => 'test name',
        'mtime' => 123456789,
      ],
    ], $infos);
  }

  /**
   * @covers ::getPathNames
   */
  public function testGetPathNames(): void {
    $test_extension_list = $this->setupTestExtensionList();

    $filenames = $test_extension_list->getPathNames();
    $this->assertEquals([
      'test_name' => 'example/test_name/test_name.info.yml',
    ], $filenames);
  }

  /**
   * @covers ::getPathname
   */
  public function testGetPathname(): void {
    $test_extension_list = $this->setupTestExtensionList();

    $pathname = $test_extension_list->getPathname('test_name');
    $this->assertEquals('example/test_name/test_name.info.yml', $pathname);
  }

  /**
   * @covers ::setPathname
   * @covers ::getPathname
   */
  public function testSetPathname(): void {
    $test_extension_list = $this->setupTestExtensionList();

    $test_extension_list->setPathname('test_name', 'vfs://drupal_root/example2/test_name/test_name.info.yml');
    $this->assertEquals('vfs://drupal_root/example2/test_name/test_name.info.yml', $test_extension_list->getPathname('test_name'));
  }

  /**
   * @covers ::getPath
   */
  public function testGetPath(): void {
    $test_extension_list = $this->setupTestExtensionList();

    $path = $test_extension_list->getPath('test_name');
    $this->assertEquals('example/test_name', $path);
  }

  /**
   * @covers ::reset
   */
  public function testReset(): void {
    $test_extension_list = $this->setupTestExtensionList();

    $path = $test_extension_list->getPath('test_name');
    $this->assertEquals('example/test_name', $path);
    $pathname = $test_extension_list->getPathname('test_name');
    $this->assertEquals('example/test_name/test_name.info.yml', $pathname);
    $filenames = $test_extension_list->getPathNames();
    $this->assertEquals([
      'test_name' => 'example/test_name/test_name.info.yml',
    ], $filenames);

    $test_extension_list->reset();

    // Ensure that everything is still usable after the resetting.
    $path = $test_extension_list->getPath('test_name');
    $this->assertEquals('example/test_name', $path);
    $pathname = $test_extension_list->getPathname('test_name');
    $this->assertEquals('example/test_name/test_name.info.yml', $pathname);
    $filenames = $test_extension_list->getPathNames();
    $this->assertEquals([
      'test_name' => 'example/test_name/test_name.info.yml',
    ], $filenames);
  }

  /**
   * @covers ::checkIncompatibility
   *
   * @dataProvider providerCheckIncompatibility
   */
  public function testCheckIncompatibility($additional_settings, $expected): void {
    $test_extension_list = $this->setupTestExtensionList(['test_name'], $additional_settings);
    $this->assertSame($expected, $test_extension_list->checkIncompatibility('test_name'));
  }

  /**
   * DataProvider for testCheckIncompatibility().
   */
  public static function providerCheckIncompatibility() {
    return [
      'core_incompatible true' => [
        [
          'core_incompatible' => TRUE,
        ],
        TRUE,
      ],
      'core_incompatible false' => [
        [
          'core_incompatible' => FALSE,
        ],
        FALSE,
      ],
      'PHP 1, core_incompatible FALSE' => [
        [
          'core_incompatible' => FALSE,
          'php' => 1,
        ],
        FALSE,
      ],
      'PHP 1000000000000, core_incompatible FALSE' => [
        [
          'core_incompatible' => FALSE,
          'php' => 1000000000000,
        ],
        TRUE,
      ],
      'PHP 1, core_incompatible TRUE' => [
        [
          'core_incompatible' => TRUE,
          'php' => 1,
        ],
        TRUE,
      ],
      'PHP 1000000000000, core_incompatible TRUE' => [
        [
          'core_incompatible' => TRUE,
          'php' => 1000000000000,
        ],
        TRUE,
      ],
    ];
  }

  /**
   * Sets up an a test extension list.
   *
   * @param string[] $extension_names
   *   The names of the extensions to create.
   * @param mixed[] $additional_info_values
   *   The additional values to add to extensions info.yml files. These values
   *   will be encoded using '\Drupal\Component\Serialization\Yaml::encode()'.
   *   The array keys should be valid top level yaml file keys.
   *
   * @return \Drupal\Tests\Core\Extension\TestExtension
   *   The test extension list.
   */
  protected function setupTestExtensionList(array $extension_names = ['test_name'], array $additional_info_values = []) {
    vfsStream::setup('drupal_root');

    $folders = ['example' => []];
    foreach ($extension_names as $extension_name) {
      $folders['example'][$extension_name][$extension_name . '.info.yml'] = Yaml::encode([
        'name' => 'test name',
        'type' => 'test_extension',
        'core' => '8.x',
      ] + $additional_info_values);
    }
    vfsStream::create($folders);
    foreach ($extension_names as $extension_name) {
      touch("vfs://drupal_root/example/$extension_name/$extension_name.info.yml", 123456789);
    }

    [$cache, $info_parser, $module_handler, $state] = $this->getMocks();
    $info_parser->parse(Argument::any())->will(function ($args) {
      return Yaml::decode(file_get_contents('vfs://drupal_root/' . $args[0]));
    });

    $test_extension_list = new TestExtension('vfs://drupal_root', 'test_extension', $cache->reveal(), $info_parser->reveal(), $module_handler->reveal(), $state->reveal(), 'testing');

    $extension_discovery = $this->prophesize(ExtensionDiscovery::class);
    $extension_scan_result = [];
    foreach ($extension_names as $extension_name) {
      $extension_scan_result[$extension_name] = new Extension('vfs://drupal_root', 'test_extension', "example/$extension_name/$extension_name.info.yml");
    }
    $extension_discovery->scan('test_extension')->willReturn($extension_scan_result);
    $test_extension_list->setExtensionDiscovery($extension_discovery->reveal());
    return $test_extension_list;
  }

  protected function getMocks() {
    $cache = $this->prophesize(CacheBackendInterface::class);
    $info_parser = $this->prophesize(InfoParserInterface::class);
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $state = $this->prophesize(StateInterface::class);
    return [$cache, $info_parser, $module_handler, $state];
  }

}

class TestExtension extends ExtensionList {

  /**
   * @var string[]
   */
  protected $installedExtensions = [];

  /**
   * @var \Drupal\Core\Extension\ExtensionDiscovery|null
   */
  protected $extensionDiscovery;

  /**
   * @param \Drupal\Core\Extension\ExtensionDiscovery $extension_discovery
   *   The extension discovery class.
   */
  public function setExtensionDiscovery(ExtensionDiscovery $extension_discovery) {
    $this->extensionDiscovery = $extension_discovery;
  }

  public function setInstalledExtensions(array $extension_names) {
    $this->installedExtensions = $extension_names;
  }

  /**
   * {@inheritdoc}
   */
  protected function getInstalledExtensionNames() {
    return $this->installedExtensions;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExtensionDiscovery() {
    return $this->extensionDiscovery ?: parent::getExtensionDiscovery();
  }

}
