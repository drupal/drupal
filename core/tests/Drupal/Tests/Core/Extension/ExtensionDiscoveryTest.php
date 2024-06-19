<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Extension;

use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests discovery of extensions.
 *
 * @coversDefaultClass \Drupal\Core\Extension\ExtensionDiscovery
 * @group Extension
 */
class ExtensionDiscoveryTest extends UnitTestCase {

  /**
   * Tests extension discovery in a virtual filesystem with vfsStream.
   *
   * @covers ::scan
   */
  public function testExtensionDiscoveryVfs(): void {

    // Set up the file system.
    $filesystem = [];
    $files_by_type_and_name_expected = $this->populateFilesystemStructure($filesystem);

    $vfs = vfsStream::setup('root', NULL, $filesystem);
    $root = $vfs->url();

    $this->assertFileExists($root . '/core/modules/system/system.module');
    $this->assertFileExists($root . '/core/modules/system/system.info.yml');

    // Create an ExtensionDiscovery with $root.
    $extension_discovery = new ExtensionDiscovery($root, FALSE, NULL, 'sites/default');

    /** @var \Drupal\Core\Extension\Extension[][] $extensions_by_type */
    $extensions_by_type = [];
    $files_by_type_and_name = [];
    foreach (['profile', 'module', 'theme', 'theme_engine'] as $type) {
      $extensions_by_type[$type] = $extension_discovery->scan($type, FALSE);
      foreach ($extensions_by_type[$type] as $name => $extension) {
        $files_by_type_and_name[$type][$name] = $extension->getPathname();
      }
      if ($type === 'profile') {
        // Set profile directories for discovery of the other extension types.
        $extension_discovery->setProfileDirectories(['my_profile' => 'profiles/my_profile']);
      }
    }

    $this->assertEquals($files_by_type_and_name_expected, $files_by_type_and_name);

    $extension_expected = new Extension($root, 'module', 'core/modules/system/system.info.yml', 'system.module');
    $extension_expected->subpath = 'modules/system';
    $extension_expected->origin = 'core';
    $this->assertEquals($extension_expected, $extensions_by_type['module']['system'], 'system');

    $extension_expected = new Extension($root, 'theme_engine', 'core/themes/engines/twig/twig.info.yml', 'twig.engine');
    $extension_expected->subpath = 'themes/engines/twig';
    $extension_expected->origin = 'core';
    $this->assertEquals($extension_expected, $extensions_by_type['theme_engine']['twig'], 'twig');
  }

  /**
   * Tests changing extension discovery file cache objects to arrays.
   *
   * @covers ::scan
   * @runInSeparateProcess
   */
  public function testExtensionDiscoveryCache(): void {
    // Set up an extension object in the cache to mimic site prior to changing
    // \Drupal\Core\Extension\ExtensionDiscovery::scanDirectory() to cache an
    // array instead of an object. Note we cannot use the VFS file system
    // because FileCache does not support stream wrappers.
    $extension = new Extension($this->root, 'module', 'core/modules/user/user.info.yml', 'user.module');
    $extension->subpath = 'modules/user';
    $extension->origin = 'core';
    // Undo \Drupal\Tests\UnitTestCase::setUp() so FileCache works.
    FileCacheFactory::setConfiguration([]);
    $file_cache = FileCacheFactory::get('extension_discovery');
    $file_cache->set($this->root . '/core/modules/user/user.info.yml', $extension);

    // Create an ExtensionDiscovery object to test.
    $extension_discovery = new ExtensionDiscovery($this->root, TRUE, [], 'sites/default');
    $modules = $extension_discovery->scan('module', FALSE);
    $this->assertArrayHasKey('user', $modules);
    $this->assertEquals((array) $extension, (array) $modules['user']);
    $this->assertNotSame($extension, $modules['user']);
    // FileCache item should now be an array.
    $this->assertSame([
      'type' => 'module',
      'pathname' => 'core/modules/user/user.info.yml',
      'filename' => 'user.module',
      'subpath' => 'modules/user',
    ], $file_cache->get($this->root . '/core/modules/user/user.info.yml'));
  }

  /**
   * Tests finding modules that have a trailing comment on the type property.
   *
   * @covers ::scan
   */
  public function testExtensionDiscoveryTypeComment(): void {
    $extension_discovery = new ExtensionDiscovery($this->root, TRUE, [], 'sites/default');
    $modules = $extension_discovery->scan('module', TRUE);
    $this->assertArrayHasKey('module_info_type_comment', $modules);
  }

  /**
   * Adds example files to the filesystem structure.
   *
   * @param array $filesystem_structure
   *   An associative array where each key represents a directory.
   *
   * @return string[][]
   *   Format: $[$type][$name] = $yml_file
   *   E.g. $['module']['system'] = 'system.info.yml'
   */
  protected function populateFilesystemStructure(array &$filesystem_structure) {
    $info_by_file = [
      'core/profiles/standard/standard.info.yml' => [
        'type' => 'profile',
      ],
      'core/profiles/minimal/minimal.info.yml' => [
        'type' => 'profile',
      ],
      'core/themes/test_theme/test_theme.info.yml' => [
        'type' => 'theme',
      ],
      // Override the core instance of the 'test_theme' theme.
      'sites/default/themes/test_theme/test_theme.info.yml' => [
        'type' => 'theme',
      ],
      // Override the core instance of the 'minimal' profile.
      'sites/default/profiles/minimal/minimal.info.yml' => [
        'type' => 'profile',
      ],
      'profiles/my_profile/my_profile.info.yml' => [
        'type' => 'profile',
      ],
      'profiles/my_profile/modules/my_profile_nested_module/my_profile_nested_module.info.yml' => [],
      'profiles/other_profile/other_profile.info.yml' => [
        'type' => 'profile',
      ],
      'core/modules/user/user.info.yml' => [],
      'profiles/other_profile/modules/other_profile_nested_module/other_profile_nested_module.info.yml' => [],
      'core/modules/system/system.info.yml' => [],
      'modules/devel/devel.info.yml' => [],
      'modules/poorly_placed_theme/poorly_placed_theme.info.yml' => [
        'type' => 'theme',
      ],
      'core/themes/engines/twig/twig.info.yml' => [
        'type' => 'theme_engine',
      ],
    ];

    $files_by_type_and_name_expected = [];
    $content_by_file = [];
    foreach ($info_by_file as $file => $info) {
      $name = basename($file, '.info.yml');
      $info += [
        'type' => 'module',
        'name' => "Name of ($name)",
        'core' => '8.x',
      ];
      $type = $info['type'];
      $content_by_file[$file] = Yaml::dump($info);
      $files_by_type_and_name_expected[$type][$name] = $file;
    }

    $content_by_file['core/modules/system/system.module'] = '<?php';
    $content_by_file['core/themes/engines/twig/twig.engine'] = '<?php';

    foreach ($content_by_file as $file => $content) {
      $pieces = explode('/', $file);
      $this->addFileToFilesystemStructure($filesystem_structure, $pieces, $content);
    }

    unset($files_by_type_and_name_expected['module']['other_profile_nested_module']);

    return $files_by_type_and_name_expected;
  }

  /**
   * @param array $filesystem_structure
   *   An associative array where each key represents a directory.
   * @param string[] $pieces
   *   Fragments of the file path.
   * @param string $content
   *   The contents of the file.
   */
  protected function addFileToFilesystemStructure(array &$filesystem_structure, array $pieces, $content) {
    $piece = array_shift($pieces);
    if ($pieces !== []) {
      $filesystem_structure += [$piece => []];
      $this->addFileToFilesystemStructure($filesystem_structure[$piece], $pieces, $content);
    }
    else {
      $filesystem_structure[$piece] = $content;
    }
  }

}
