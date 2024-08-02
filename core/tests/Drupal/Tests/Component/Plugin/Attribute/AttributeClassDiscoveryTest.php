<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Plugin\Attribute;

use Composer\Autoload\ClassLoader;
use Drupal\Component\Plugin\Discovery\AttributeClassDiscovery;
use Drupal\Component\FileCache\FileCacheFactory;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Plugin\Discovery\AttributeClassDiscovery
 * @group Attribute
 * @runTestsInSeparateProcesses
 */
class AttributeClassDiscoveryTest extends TestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Ensure the file cache is disabled.
    FileCacheFactory::setConfiguration([FileCacheFactory::DISABLE_CACHE => TRUE]);
    // Ensure that FileCacheFactory has a prefix.
    FileCacheFactory::setPrefix('prefix');

    // Normally the attribute classes would be autoloaded.
    include_once __DIR__ . '/../../../../../fixtures/plugins/CustomPlugin.php';

    $additionalClassLoader = new ClassLoader();
    $additionalClassLoader->addPsr4("com\\example\\PluginNamespace\\", __DIR__ . "/../../../../../fixtures/plugins/Plugin/PluginNamespace");
    $additionalClassLoader->register(TRUE);
  }

  /**
   * @covers ::__construct
   * @covers ::getPluginNamespaces
   */
  public function testGetPluginNamespaces(): void {
    // Path to the classes which we'll discover and parse annotation.
    $discovery = new AttributeClassDiscovery(['com/example' => [__DIR__]]);

    $reflection = new \ReflectionMethod($discovery, 'getPluginNamespaces');
    $reflection->setAccessible(TRUE);

    $result = $reflection->invoke($discovery);
    $this->assertEquals(['com/example' => [__DIR__]], $result);
  }

  /**
   * @covers ::getDefinitions
   * @covers ::prepareAttributeDefinition
   */
  public function testGetDefinitions(): void {
    $discovery = new AttributeClassDiscovery(['com\example' => [__DIR__ . "/../../../../../fixtures/plugins/Plugin"]]);
    $this->assertEquals([
      'discovery_test_1' => [
        'id' => 'discovery_test_1',
        'class' => 'com\example\PluginNamespace\AttributeDiscoveryTest1',
      ],
    ], $discovery->getDefinitions());

    $custom_annotation_discovery = new AttributeClassDiscovery(['com\example' => [__DIR__ . "/../../../../../fixtures/plugins/Plugin"]], 'com\example\PluginNamespace\CustomPlugin');
    $this->assertEquals([
      'discovery_test_1' => [
        'id' => 'discovery_test_1',
        'class' => 'com\example\PluginNamespace\AttributeDiscoveryTest1',
        'title' => 'Discovery test plugin',
      ],
    ], $custom_annotation_discovery->getDefinitions());

    $empty_discovery = new AttributeClassDiscovery(['com\example' => [__DIR__ . "/../../../../../fixtures/plugins/Plugin"]], 'com\example\PluginNamespace\CustomPlugin2');
    $this->assertEquals([], $empty_discovery->getDefinitions());
  }

}
