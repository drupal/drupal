<?php

namespace Drupal\Tests\Core\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use PHPUnit\Framework\MockObject\Matcher\InvokedRecorder;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
 * @group Plugin
 */
class DefaultSingleLazyPluginCollectionTest extends LazyPluginCollectionTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setupPluginCollection(InvokedRecorder $create_count = NULL) {
    $definitions = $this->getPluginDefinitions();
    $this->pluginInstances['apple'] = new ConfigurablePlugin(['id' => 'apple', 'key' => 'value'], 'apple', $definitions['apple']);
    $this->pluginInstances['banana'] = new ConfigurablePlugin(['id' => 'banana', 'key' => 'other_value'], 'banana', $definitions['banana']);

    $create_count = $create_count ?: $this->never();
    $this->pluginManager->expects($create_count)
      ->method('createInstance')
      ->willReturnCallback(function ($id) {
        return $this->pluginInstances[$id];
      });

    $this->defaultPluginCollection = new DefaultSingleLazyPluginCollection($this->pluginManager, 'apple', ['id' => 'apple', 'key' => 'value']);
  }

  /**
   * Tests the get() method.
   */
  public function testGet() {
    $this->setupPluginCollection($this->once());
    $apple = $this->pluginInstances['apple'];

    $this->assertSame($apple, $this->defaultPluginCollection->get('apple'));
  }

  /**
   * @covers ::addInstanceId
   * @covers ::getConfiguration
   * @covers ::setConfiguration
   */
  public function testAddInstanceId() {
    $this->setupPluginCollection($this->any());

    $this->assertEquals(['id' => 'apple', 'key' => 'value'], $this->defaultPluginCollection->get('apple')->getConfiguration());
    $this->assertEquals(['id' => 'apple', 'key' => 'value'], $this->defaultPluginCollection->getConfiguration());

    $this->defaultPluginCollection->addInstanceId('banana', ['id' => 'banana', 'key' => 'other_value']);

    $this->assertEquals(['id' => 'apple', 'key' => 'value'], $this->defaultPluginCollection->get('apple')->getConfiguration());
    $this->assertEquals(['id' => 'banana', 'key' => 'other_value'], $this->defaultPluginCollection->getConfiguration());
    $this->assertEquals(['id' => 'banana', 'key' => 'other_value'], $this->defaultPluginCollection->get('banana')->getConfiguration());
  }

  /**
   * @covers ::getInstanceIds
   */
  public function testGetInstanceIds() {
    $this->setupPluginCollection($this->any());
    $this->assertEquals(['apple' => 'apple'], $this->defaultPluginCollection->getInstanceIds());

    $this->defaultPluginCollection->addInstanceId('banana', ['id' => 'banana', 'key' => 'other_value']);
    $this->assertEquals(['banana' => 'banana'], $this->defaultPluginCollection->getInstanceIds());
  }

}

class ConfigurablePlugin extends PluginBase implements ConfigurableInterface {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  public function defaultConfiguration() {
    return [];
  }

  public function getConfiguration() {
    return $this->configuration;
  }

  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

}
