<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\migrate\Plugin\migrate\process\FileCopy;
use Drupal\migrate\Plugin\MigrateProcessInterface;

/**
 * Flag for dealing with existing files: Appends number until name is unique.
 */
define('FILE_EXISTS_RENAME', 0);

/**
 * Flag for dealing with existing files: Replace the existing file.
 */
define('FILE_EXISTS_REPLACE', 1);

/**
 * Flag for dealing with existing files: Do nothing and return FALSE.
 */
define('FILE_EXISTS_ERROR', 2);

/**
 * Tests the file copy process plugin.
 *
 * @group migrate
 * @group legacy
 *
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\process\FileCopy
 */
class FileCopyTest extends MigrateProcessTestCase {

  /**
   * Tests that the rename configuration key will trigger a deprecation notice.
   *
   * @dataProvider providerDeprecationNoticeRename
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param $expected
   *   The expected value of the plugin configuration.
   *
   * @expectedDeprecation Using the key 'rename' is deprecated, use 'file_exists' => 'rename' instead. See https://www.drupal.org/node/2981389.
   */
  public function testDeprecationNoticeRename($configuration, $expected) {
    $this->assertPlugin($configuration, $expected);
  }

  /**
   * Data provider for testDeprecationNoticeRename.
   */
  public function providerDeprecationNoticeRename() {
    return [
      [['rename' => TRUE], FILE_EXISTS_RENAME],
      [['rename' => FALSE], FILE_EXISTS_REPLACE],
    ];
  }

  /**
   * Tests that the reuse configuration key will trigger a deprecation notice.
   *
   * @dataProvider providerDeprecationNoticeReuse
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param $expected
   *   The expected value of the plugin configuration.
   *
   * @expectedDeprecation Using the key 'reuse' is deprecated, use 'file_exists' => 'use existing' instead. See https://www.drupal.org/node/2981389.
   */
  public function testDeprecationNoticeReuse($configuration, $expected) {
    $this->assertPlugin($configuration, $expected);
  }

  /**
   * Data provider for testDeprecationNoticeReuse.
   */
  public function providerDeprecationNoticeReuse() {
    return [
      [['reuse' => TRUE], FILE_EXISTS_ERROR],
      [['reuse' => FALSE], FILE_EXISTS_REPLACE],
    ];
  }

  /**
   * Tests that the plugin constructor correctly sets the configuration.
   *
   * @dataProvider providerFileProcessBaseConstructor
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param $expected
   *   The expected value of the plugin configuration.
   */
  public function testFileProcessBaseConstructor($configuration, $expected) {
    $this->assertPlugin($configuration, $expected);
  }

  /**
   * Data provider for testFileProcessBaseConstructor.
   */
  public function providerFileProcessBaseConstructor() {
    return [
      [['file_exists' => 'replace'], FILE_EXISTS_REPLACE],
      [['file_exists' => 'rename'], FILE_EXISTS_RENAME],
      [['file_exists' => 'use existing'], FILE_EXISTS_ERROR],
      [['file_exists' => 'foobar'], FILE_EXISTS_REPLACE],
      [[], FILE_EXISTS_REPLACE],
    ];
  }

  /**
   * Creates a TestFileCopy process plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param $expected
   *   The expected value of the plugin configuration.
   */
  protected function assertPlugin($configuration, $expected) {
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
