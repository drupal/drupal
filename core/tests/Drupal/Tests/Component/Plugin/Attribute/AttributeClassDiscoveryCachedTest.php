<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Plugin\Attribute;

use Drupal\Component\Plugin\Discovery\AttributeClassDiscovery;
use Drupal\Component\FileCache\FileCacheFactory;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Plugin\Discovery\AttributeClassDiscovery
 * @group Attribute
 * @runTestsInSeparateProcesses
 */
class AttributeClassDiscoveryCachedTest extends TestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Ensure FileCacheFactory::DISABLE_CACHE is *not* set, since we're testing
    // integration with the file cache.
    FileCacheFactory::setConfiguration([]);
    // Ensure that FileCacheFactory has a prefix.
    FileCacheFactory::setPrefix('prefix');

    // Normally the attribute classes would be autoloaded.
    include_once __DIR__ . '/Fixtures/CustomPlugin.php';
    include_once __DIR__ . '/Fixtures/Plugins/PluginNamespace/AttributeDiscoveryTest1.php';
  }

  /**
   * Tests that getDefinitions() retrieves the file cache correctly.
   *
   * @covers ::getDefinitions
   */
  public function testGetDefinitions() {
    // Path to the classes which we'll discover and parse annotation.
    $discovery_path = __DIR__ . '/Fixtures/Plugins';
    // File path that should be discovered within that directory.
    $file_path = $discovery_path . '/PluginNamespace/AttributeDiscoveryTest1.php';

    $discovery = new AttributeClassDiscovery(['com\example' => [$discovery_path]]);
    $this->assertEquals([
      'discovery_test_1' => [
        'id' => 'discovery_test_1',
        'class' => 'com\example\PluginNamespace\AttributeDiscoveryTest1',
      ],
    ], $discovery->getDefinitions());

    // Gain access to the file cache so we can change it.
    $ref_file_cache = new \ReflectionProperty($discovery, 'fileCache');
    $ref_file_cache->setAccessible(TRUE);
    /** @var \Drupal\Component\FileCache\FileCacheInterface $file_cache */
    $file_cache = $ref_file_cache->getValue($discovery);
    // The file cache is keyed by the file path, and we'll add some known
    // content to test against.
    $file_cache->set($file_path, [
      'id' => 'wrong_id',
      'content' => serialize(['an' => 'array']),
    ]);

    // Now perform the same query and check for the cached results.
    $this->assertEquals([
      'wrong_id' => [
        'an' => 'array',
      ],
    ], $discovery->getDefinitions());
  }

}
