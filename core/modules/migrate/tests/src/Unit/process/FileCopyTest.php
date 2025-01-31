<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\migrate\Plugin\migrate\process\FileCopy;
use Drupal\migrate\Plugin\MigrateProcessInterface;

/**
 * Tests the file copy process plugin.
 *
 * @group migrate
 *
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\process\FileCopy
 */
class FileCopyTest extends MigrateProcessTestCase {

  /**
   * Tests that the plugin constructor correctly sets the configuration.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param \Drupal\Core\File\FileExists $expected
   *   The expected value of the plugin configuration.
   *
   * @dataProvider providerFileProcessBaseConstructor
   */
  public function testFileProcessBaseConstructor(array $configuration, FileExists $expected): void {
    $this->assertPlugin($configuration, $expected);
  }

  /**
   * Data provider for testFileProcessBaseConstructor.
   */
  public static function providerFileProcessBaseConstructor() {
    return [
      [['file_exists' => 'replace'], FileExists::Replace],
      [['file_exists' => 'rename'], FileExists::Rename],
      [['file_exists' => 'use existing'], FileExists::Error],
      [['file_exists' => 'foobar'], FileExists::Replace],
      [[], FileExists::Replace],
    ];
  }

  /**
   * Creates a TestFileCopy process plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param \Drupal\Core\File\FileExists $expected
   *   The expected value of the plugin configuration.
   *
   * @internal
   */
  protected function assertPlugin(array $configuration, FileExists $expected): void {
    $stream_wrapper_manager = $this->prophesize(StreamWrapperManagerInterface::class)->reveal();
    $file_system = $this->prophesize(FileSystemInterface::class)->reveal();
    $download_plugin = $this->prophesize(MigrateProcessInterface::class)->reveal();
    $this->plugin = new TestFileCopy($configuration, 'test', [], $stream_wrapper_manager, $file_system, $download_plugin);
    $plugin_config = $this->plugin->getConfiguration();
    $this->assertArrayHasKey('file_exists', $plugin_config);
    $this->assertSame($expected, $plugin_config['file_exists']);
  }

}

/**
 * Class for testing FileCopy.
 */
class TestFileCopy extends FileCopy {

  /**
   * Gets this plugin's configuration.
   *
   * @return array
   *   An array of this plugin's configuration.
   */
  public function getConfiguration() {
    return $this->configuration;
  }

}
