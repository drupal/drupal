<?php

namespace Drupal\Tests\Core\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\Tests\Core\Plugin\Fixtures\TestConfigurablePlugin;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\DefaultLazyPluginCollection
 * @group Plugin
 */
class DefaultLazyPluginCollectionTest extends LazyPluginCollectionTestBase {

  /**
   * Stores all setup plugin instances.
   *
   * @var \Drupal\Component\Plugin\ConfigurableInterface[]
   */
  protected $pluginInstances;

  /**
   * @covers ::has
   */
  public function testHas() {
    $this->setupPluginCollection();
    $definitions = $this->getPluginDefinitions();

    $this->assertFalse($this->defaultPluginCollection->has($this->randomMachineName()), 'Nonexistent plugin found.');

    foreach (array_keys($definitions) as $plugin_id) {
      $this->assertTrue($this->defaultPluginCollection->has($plugin_id));
    }
  }

  /**
   * @covers ::get
   */
  public function testGet() {
    $this->setupPluginCollection($this->once());
    $apple = $this->pluginInstances['apple'];

    $this->assertSame($apple, $this->defaultPluginCollection->get('apple'));
  }

  /**
   * @covers ::get
   */
  public function testGetNotExistingPlugin() {
    $this->setupPluginCollection();
    $this->expectException(PluginNotFoundException::class);
    $this->expectExceptionMessage("Plugin ID 'pear' was not found.");
    $this->defaultPluginCollection->get('pear');
  }

  /**
   * Provides test data for testSortHelper.
   *
   * @return array
   *   The test data.
   */
  public function providerTestSortHelper() {
    return [
      ['apple', 'apple', 0],
      ['apple', 'cherry', -1],
      ['cherry', 'apple', 1],
      ['cherry', 'banana', 1],
    ];
  }

  /**
   * @param string $plugin_id_1
   *   The first plugin ID.
   * @param string $plugin_id_2
   *   The second plugin ID.
   * @param int $expected
   *   The expected result.
   *
   * @covers ::sortHelper
   * @dataProvider providerTestSortHelper
   */
  public function testSortHelper($plugin_id_1, $plugin_id_2, $expected) {
    $this->setupPluginCollection($this->any());
    if ($expected != 0) {
      $expected = $expected > 0 ? 1 : -1;
    }
    $this->assertEquals($expected, $this->defaultPluginCollection->sortHelper($plugin_id_1, $plugin_id_2));
  }

  /**
   * @covers ::getConfiguration
   */
  public function testGetConfiguration() {
    $this->setupPluginCollection($this->exactly(3));
    // The expected order matches $this->config.
    $expected = ['banana', 'cherry', 'apple'];

    $config = $this->defaultPluginCollection->getConfiguration();
    $this->assertSame($expected, array_keys($config), 'The order of the configuration is unchanged.');

    $ids = $this->defaultPluginCollection->getInstanceIds();
    $this->assertSame($expected, array_keys($ids), 'The order of the instances is unchanged.');

    $this->defaultPluginCollection->sort();
    $config = $this->defaultPluginCollection->getConfiguration();
    $this->assertSame($expected, array_keys($config), 'After sorting, the order of the configuration is unchanged.');

    $ids = $this->defaultPluginCollection->getInstanceIds();
    sort($expected);
    $this->assertSame($expected, array_keys($ids), 'After sorting, the order of the instances is also sorted.');
  }

  /**
   * @covers ::addInstanceId
   */
  public function testAddInstanceId() {
    $this->setupPluginCollection($this->exactly(4));
    $expected = [
      'banana' => 'banana',
      'cherry' => 'cherry',
      'apple' => 'apple',
    ];
    $this->defaultPluginCollection->addInstanceId('apple');
    $result = $this->defaultPluginCollection->getInstanceIds();
    $this->assertSame($expected, $result);
    $this->assertSame($expected, array_intersect_key($result, $this->defaultPluginCollection->getConfiguration()));

    $expected = [
      'cherry' => 'cherry',
      'apple' => 'apple',
      'banana' => 'banana',
    ];
    $this->defaultPluginCollection->removeInstanceId('banana');
    $this->defaultPluginCollection->addInstanceId('banana', $this->config['banana']);

    $result = $this->defaultPluginCollection->getInstanceIds();
    $this->assertSame($expected, $result);
    $this->assertSame($expected, array_intersect_key($result, $this->defaultPluginCollection->getConfiguration()));
  }

  /**
   * @covers ::removeInstanceId
   */
  public function testRemoveInstanceId() {
    $this->setupPluginCollection($this->exactly(2));
    $this->defaultPluginCollection->removeInstanceId('cherry');
    $config = $this->defaultPluginCollection->getConfiguration();
    $this->assertArrayNotHasKey('cherry', $config, 'After removing an instance, the configuration is updated.');
  }

  /**
   * @covers ::setInstanceConfiguration
   */
  public function testSetInstanceConfiguration() {
    $this->setupPluginCollection($this->exactly(3));
    $expected = [
      'id' => 'cherry',
      'key' => 'value',
      'custom' => 'bananas',
    ];
    $this->defaultPluginCollection->setInstanceConfiguration('cherry', $expected);
    $config = $this->defaultPluginCollection->getConfiguration();
    $this->assertSame($expected, $config['cherry']);
  }

  /**
   * @covers ::count
   */
  public function testCount() {
    $this->setupPluginCollection();
    $this->assertSame(3, $this->defaultPluginCollection->count());
  }

  /**
   * @covers ::clear
   */
  public function testClear() {
    $this->setupPluginCollection($this->exactly(6));
    $this->defaultPluginCollection->getConfiguration();
    $this->defaultPluginCollection->getConfiguration();
    $this->defaultPluginCollection->clear();
    $this->defaultPluginCollection->getConfiguration();
  }

  /**
   * @covers ::set
   */
  public function testSet() {
    $this->setupPluginCollection($this->exactly(4));
    $instance = $this->pluginManager->createInstance('cherry', $this->config['cherry']);
    $this->defaultPluginCollection->set('cherry2', $instance);
    $this->defaultPluginCollection->setInstanceConfiguration('cherry2', $this->config['cherry']);

    $expected = [
      'banana',
      'cherry',
      'apple',
      'cherry2',
    ];
    $config = $this->defaultPluginCollection->getConfiguration();
    $this->assertSame($expected, array_keys($config));
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginMock($plugin_id, array $definition) {
    return new TestConfigurablePlugin($this->config[$plugin_id], $plugin_id, $definition);
  }

  /**
   * @covers ::getConfiguration
   */
  public function testConfigurableGetConfiguration() {
    $this->setupPluginCollection($this->exactly(3));
    $config = $this->defaultPluginCollection->getConfiguration();
    $this->assertSame($this->config, $config);
  }

  /**
   * @covers ::setConfiguration
   */
  public function testConfigurableSetConfiguration() {
    $this->setupPluginCollection($this->exactly(2));

    $this->defaultPluginCollection->setConfiguration(['apple' => ['value' => 'pineapple', 'id' => 'apple']]);
    $config = $this->defaultPluginCollection->getConfiguration();
    $this->assertSame(['apple' => ['value' => 'pineapple', 'id' => 'apple']], $config);
    $plugin = $this->pluginInstances['apple'];
    $this->assertSame(['value' => 'pineapple', 'id' => 'apple'], $plugin->getConfiguration());

    $this->defaultPluginCollection->setConfiguration(['cherry' => ['value' => 'kiwi', 'id' => 'cherry']]);
    $expected['cherry'] = ['value' => 'kiwi', 'id' => 'cherry'];
    $config = $this->defaultPluginCollection->getConfiguration();
    $this->assertSame(['cherry' => ['value' => 'kiwi', 'id' => 'cherry']], $config);

  }

  /**
   * Tests that plugin methods are correctly attached to interfaces.
   *
   * @covers ::getConfiguration
   */
  public function testConfigurableInterface() {
    $configurable_plugin = $this->prophesize(ConfigurableInterface::class);
    $configurable_config = ['id' => 'configurable', 'foo' => 'bar'];
    $configurable_plugin->getConfiguration()->willReturn($configurable_config);

    $nonconfigurable_plugin = $this->prophesize(PluginInspectionInterface::class);
    $nonconfigurable_config = ['id' => 'non-configurable', 'baz' => 'qux'];
    $nonconfigurable_plugin->configuration = $nonconfigurable_config;

    $configurations = [
      'configurable' => $configurable_config,
      'non-configurable' => $nonconfigurable_config,
    ];

    $plugin_manager = $this->prophesize(PluginManagerInterface::class);
    $plugin_manager->createInstance('configurable', $configurable_config)->willReturn($configurable_plugin->reveal());
    $plugin_manager->createInstance('non-configurable', $nonconfigurable_config)->willReturn($nonconfigurable_plugin->reveal());

    $collection = new DefaultLazyPluginCollection($plugin_manager->reveal(), $configurations);
    $this->assertSame($configurations, $collection->getConfiguration());

  }

}
