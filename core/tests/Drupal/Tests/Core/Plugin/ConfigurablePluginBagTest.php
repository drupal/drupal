<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Plugin\ConfigurablePluginBagTest.
 */

namespace Drupal\Tests\Core\Plugin;

use Drupal\Tests\Core\Plugin\Fixtures\TestConfigurablePlugin;

/**
 * @coversDefaultClass \Drupal\Component\Plugin\ConfigurablePluginInterface
 * @group Plugin
 */
class ConfigurablePluginBagTest extends PluginBagTestBase {

  /**
   * Stores all setup plugin instances.
   *
   * @var \Drupal\Component\Plugin\ConfigurablePluginInterface[]
   */
  protected $pluginInstances;

  /**
   * {@inheritdoc}
   */
  protected function getPluginMock($plugin_id, array $definition) {
    return new TestConfigurablePlugin($this->config[$plugin_id], $plugin_id, $definition);
  }

  /**
   * Tests the getConfiguration() method with configurable plugins.
   */
  public function testConfigurableGetConfiguration() {
    $this->setupPluginBag($this->exactly(3));
    $config = $this->defaultPluginBag->getConfiguration();
    $this->assertSame($this->config, $config);
  }

  /**
   * Tests the setConfiguration() method with configurable plugins.
   */
  public function testConfigurableSetConfiguration() {
    $this->setupPluginBag($this->exactly(3));
    $this->defaultPluginBag->getConfiguration();
    $this->defaultPluginBag->setInstanceConfiguration('apple', array('value' => 'pineapple'));

    $expected = $this->config;
    $expected['apple'] = array('value' => 'pineapple');
    $config = $this->defaultPluginBag->getConfiguration();
    $this->assertSame($expected, $config);
    $plugin = $this->pluginInstances['apple'];
    $this->assertSame($expected['apple'], $plugin->getConfiguration());
  }

}
