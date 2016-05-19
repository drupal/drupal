<?php

namespace Drupal\Tests\Core\Extension;

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
  public function testExtensionDiscoveryVfs() {

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
        $extension_discovery->setProfileDirectories(['myprofile' => 'profiles/myprofile']);
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
   * Adds example files to the filesystem structure.
   *
   * @param array $filesystem_structure
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
      // Override the core instance of the 'minimal' profile.
      'sites/default/profiles/minimal/minimal.info.yml' => [
        'type' => 'profile',
      ],
      'profiles/myprofile/myprofile.info.yml' => [
        'type' => 'profile',
      ],
      'profiles/myprofile/modules/myprofile_nested_module/myprofile_nested_module.info.yml' => [],
      'profiles/otherprofile/otherprofile.info.yml' => [
        'type' => 'profile',
      ],
      'core/modules/user/user.info.yml' => [],
      'profiles/otherprofile/modules/otherprofile_nested_module/otherprofile_nested_module.info.yml' => [],
      'core/modules/system/system.info.yml' => [],
      'core/themes/seven/seven.info.yml' => [
        'type' => 'theme',
      ],
      // Override the core instance of the 'seven' theme.
      'sites/default/themes/seven/seven.info.yml' => [
        'type' => 'theme',
      ],
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

    unset($files_by_type_and_name_expected['module']['otherprofile_nested_module']);

    return $files_by_type_and_name_expected;
  }

  /**
   * @param array $filesystem_structure
   * @param string[] $pieces
   *   Fragments of the file path.
   * @param string $content
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
