<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Plugin;

use Drupal\Core\Plugin\ConfigurablePluginBase;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;

/**
 * Tests Drupal\Core\Plugin\DefaultSingleLazyPluginCollection.
 */
#[CoversClass(DefaultSingleLazyPluginCollection::class)]
#[Group('Plugin')]
class DefaultSingleLazyPluginCollectionTest extends LazyPluginCollectionTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setupPluginCollection(?InvocationOrder $create_count = NULL): void {
    $definitions = $this->getPluginDefinitions();
    $this->pluginInstances['apple'] = new ConfigurablePlugin(['id' => 'apple', 'key' => 'value'], 'apple', $definitions['apple']);
    $this->pluginInstances['banana'] = new ConfigurablePlugin(['id' => 'banana', 'key' => 'other_value'], 'banana', $definitions['banana']);

    $create_count = $create_count ?: $this->never();
    $this->pluginManager->expects($create_count)
      ->method('createInstance')
      ->willReturnCallback(function ($id) {
        return $this->pluginInstances[$id];
      });

    $this->defaultPluginCollection = new DefaultSingleLazyPluginCollection($this->pluginManager, 'apple', [
      'id' => 'apple',
      'key' => 'value',
    ]);
  }

  /**
   * Tests the get() method.
   */
  public function testGet(): void {
    $this->setupPluginCollection($this->once());
    $apple = $this->pluginInstances['apple'];

    $this->assertSame($apple, $this->defaultPluginCollection->get('apple'));
  }

  /**
   * Tests add instance id.
   *
   * @legacy-covers ::addInstanceId
   * @legacy-covers ::getConfiguration
   * @legacy-covers ::setConfiguration
   */
  public function testAddInstanceId(): void {
    $this->setupPluginCollection($this->any());

    $this->assertEquals(['id' => 'apple', 'key' => 'value'], $this->defaultPluginCollection->get('apple')->getConfiguration());
    $this->assertEquals(['id' => 'apple', 'key' => 'value'], $this->defaultPluginCollection->getConfiguration());

    $this->defaultPluginCollection->addInstanceId('banana', ['id' => 'banana', 'key' => 'other_value']);

    $this->assertEquals(['id' => 'apple', 'key' => 'value'], $this->defaultPluginCollection->get('apple')->getConfiguration());
    $this->assertEquals(['id' => 'banana', 'key' => 'other_value'], $this->defaultPluginCollection->getConfiguration());
    $this->assertEquals(['id' => 'banana', 'key' => 'other_value'], $this->defaultPluginCollection->get('banana')->getConfiguration());
  }

  /**
   * Tests get instance ids.
   */
  public function testGetInstanceIds(): void {
    $this->setupPluginCollection($this->any());
    $this->assertEquals(['apple' => 'apple'], $this->defaultPluginCollection->getInstanceIds());

    $this->defaultPluginCollection->addInstanceId('banana', ['id' => 'banana', 'key' => 'other_value']);
    $this->assertEquals(['banana' => 'banana'], $this->defaultPluginCollection->getInstanceIds());
  }

  /**
   * Tests configurable set configuration.
   *
   * @legacy-covers ::setConfiguration
   */
  public function testConfigurableSetConfiguration(): void {
    $this->setupPluginCollection($this->any());

    $this->defaultPluginCollection->setConfiguration(['apple' => ['value' => 'pineapple', 'id' => 'apple']]);
    $config = $this->defaultPluginCollection->getConfiguration();
    $this->assertSame(['apple' => ['value' => 'pineapple', 'id' => 'apple']], $config);
    $plugin = $this->pluginInstances['apple'];
    $this->assertSame(['apple' => ['value' => 'pineapple', 'id' => 'apple']], $plugin->getConfiguration());

    $this->defaultPluginCollection->setConfiguration([]);
    $this->assertSame([], $this->defaultPluginCollection->getConfiguration());

    $this->defaultPluginCollection->setConfiguration(['cherry' => ['value' => 'kiwi', 'id' => 'cherry']]);
    $expected['cherry'] = ['value' => 'kiwi', 'id' => 'cherry'];
    $config = $this->defaultPluginCollection->getConfiguration();
    $this->assertSame($expected, $config);
  }

}

/**
 * Stub configurable plugin class for testing.
 */
class ConfigurablePlugin extends ConfigurablePluginBase {
}
