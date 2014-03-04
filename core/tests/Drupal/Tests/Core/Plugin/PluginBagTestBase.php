<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Plugin\PluginBagTestBase.
 */

namespace Drupal\Tests\Core\Plugin;

use Drupal\Core\Plugin\DefaultPluginBag;
use Drupal\Tests\UnitTestCase;

/**
 * Provides a base class for plugin bag tests.
 */
abstract class PluginBagTestBase extends UnitTestCase {

  /**
   * The mocked plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $pluginManager;

  /**
   * The tested plugin bag.
   *
   * @var \Drupal\Core\Plugin\DefaultPluginBag|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $defaultPluginBag;

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
  protected $config = array(
    'banana' => array('id' => 'banana', 'key' => 'value'),
    'cherry' => array('id' => 'cherry', 'key' => 'value'),
    'apple' => array('id' => 'apple', 'key' => 'value'),
  );

  protected function setUp() {
    $this->pluginManager = $this->getMock('Drupal\Component\Plugin\PluginManagerInterface');
    $this->pluginManager->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($this->getPluginDefinitions()));

  }

  /**
   * Sets up the default plugin bag.
   *
   * @param \PHPUnit_Framework_MockObject_Matcher_InvokedRecorder|null $create_count
   *   (optional) The number of times that createInstance() is expected to be
   *   called. For example, $this->any(), $this->once(), $this->exactly(6).
   *   Defaults to $this->never().
   */
  protected function setupPluginBag(\PHPUnit_Framework_MockObject_Matcher_InvokedRecorder $create_count = NULL) {
    $this->pluginInstances = array();
    $map = array();
    foreach ($this->getPluginDefinitions() as $plugin_id => $definition) {
      // Create a mock plugin instance.
      $this->pluginInstances[$plugin_id] = $this->getPluginMock($plugin_id, $definition);

      $map[] = array($plugin_id, $this->config[$plugin_id], $this->pluginInstances[$plugin_id]);
    }
    $create_count = $create_count ?: $this->never();
    $this->pluginManager->expects($create_count)
      ->method('createInstance')
      ->will($this->returnCallback(array($this, 'returnPluginMap')));

    $this->defaultPluginBag = new DefaultPluginBag($this->pluginManager, $this->config);
  }

  /**
   * Return callback for createInstance.
   *
   * @param string $plugin_id
   *   The plugin ID to return the mock plugin for.
   *
   * @return \Drupal\Component\Plugin\PluginInspectionInterface|\PHPUnit_Framework_MockObject_MockObject
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
   * @return \Drupal\Component\Plugin\PluginInspectionInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected function getPluginMock($plugin_id, array $definition) {
    // Create a mock plugin instance.
    $mock = $this->getMock('Drupal\Component\Plugin\PluginInspectionInterface');
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
    $definitions = array(
      'apple' => array(
        'id' => 'apple',
        'label' => 'Apple',
        'color' => 'green',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Apple',
        'provider' => 'plugin_test',
      ),
      'banana' => array(
        'id' => 'banana',
        'label' => 'Banana',
        'color' => 'yellow',
        'uses' => array(
          'bread' => 'Banana bread',
        ),
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Banana',
        'provider' => 'plugin_test',
      ),
      'cherry' => array(
        'id' => 'cherry',
        'label' => 'Cherry',
        'color' => 'red',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Cherry',
        'provider' => 'plugin_test',
      ),
    );
    return $definitions;
  }

}
