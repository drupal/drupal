<?php

namespace Drupal\Tests\Core\Plugin;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\Matcher\InvokedRecorder;

/**
 * Provides a base class for plugin collection tests.
 */
abstract class LazyPluginCollectionTestBase extends UnitTestCase {

  /**
   * The mocked plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $pluginManager;

  /**
   * The tested plugin collection.
   *
   * @var \Drupal\Core\Plugin\DefaultLazyPluginCollection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $defaultPluginCollection;

  /**
   * Stores all setup plugin instances.
   *
   * @var \Drupal\Component\Plugin\PluginInspectionInterface[]
   */
  protected $pluginInstances;

  /**
   * Contains the plugin configuration.
   *
   * @var array
   */
  protected $config = [
    'banana' => ['id' => 'banana', 'key' => 'value'],
    'cherry' => ['id' => 'cherry', 'key' => 'value'],
    'apple' => ['id' => 'apple', 'key' => 'value'],
  ];

  protected function setUp() {
    $this->pluginManager = $this->createMock('Drupal\Component\Plugin\PluginManagerInterface');
    $this->pluginManager->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($this->getPluginDefinitions()));

  }

  /**
   * Sets up the default plugin collection.
   *
   * @param \PHPUnit\Framework\MockObject\Matcher\InvokedRecorder|null $create_count
   *   (optional) The number of times that createInstance() is expected to be
   *   called. For example, $this->any(), $this->once(), $this->exactly(6).
   *   Defaults to $this->never().
   */
  protected function setupPluginCollection(InvokedRecorder $create_count = NULL) {
    $this->pluginInstances = [];
    $map = [];
    foreach ($this->getPluginDefinitions() as $plugin_id => $definition) {
      // Create a mock plugin instance.
      $this->pluginInstances[$plugin_id] = $this->getPluginMock($plugin_id, $definition);

      $map[] = [$plugin_id, $this->config[$plugin_id], $this->pluginInstances[$plugin_id]];
    }
    $create_count = $create_count ?: $this->never();
    $this->pluginManager->expects($create_count)
      ->method('createInstance')
      ->will($this->returnCallback([$this, 'returnPluginMap']));

    $this->defaultPluginCollection = new DefaultLazyPluginCollection($this->pluginManager, $this->config);
  }

  /**
   * Return callback for createInstance.
   *
   * @param string $plugin_id
   *   The plugin ID to return the mock plugin for.
   *
   * @return \Drupal\Component\Plugin\PluginInspectionInterface|\PHPUnit\Framework\MockObject\MockObject
   *   The mock plugin object.
   */
  public function returnPluginMap($plugin_id) {
    if (isset($this->pluginInstances[$plugin_id])) {
      return $this->pluginInstances[$plugin_id];
    }
  }

  /**
   * Returns a mocked plugin object.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $definition
   *   The plugin definition.
   *
   * @return \Drupal\Component\Plugin\PluginInspectionInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected function getPluginMock($plugin_id, array $definition) {
    // Create a mock plugin instance.
    $mock = $this->createMock('Drupal\Component\Plugin\PluginInspectionInterface');
    $mock->expects($this->any())
      ->method('getPluginId')
      ->will($this->returnValue($plugin_id));
    return $mock;
  }

  /**
   * Returns some example plugin definitions.
   *
   * @return array
   *   The example plugin definitions.
   */
  protected function getPluginDefinitions() {
    $definitions = [
      'apple' => [
        'id' => 'apple',
        'label' => 'Apple',
        'color' => 'green',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Apple',
        'provider' => 'plugin_test',
      ],
      'banana' => [
        'id' => 'banana',
        'label' => 'Banana',
        'color' => 'yellow',
        'uses' => [
          'bread' => 'Banana bread',
        ],
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Banana',
        'provider' => 'plugin_test',
      ],
      'cherry' => [
        'id' => 'cherry',
        'label' => 'Cherry',
        'color' => 'red',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Cherry',
        'provider' => 'plugin_test',
      ],
    ];
    return $definitions;
  }

}
