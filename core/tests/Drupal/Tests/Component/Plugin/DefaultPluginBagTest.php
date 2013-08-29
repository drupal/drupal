<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Plugin\DefaultPluginBagTest.
 */

namespace Drupal\Tests\Component\Plugin;

use Drupal\Component\Plugin\DefaultPluginBag;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the default plugin bag.
 *
 * @see \Drupal\Component\Plugin\DefaultPluginBag
 *
 * @group Drupal
 * @group Drupal_Plugin
 */
class DefaultPluginBagTest extends UnitTestCase {

  /**
   * The mocked plugin manager.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * The tested plugin bag.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\Component\Plugin\DefaultPluginBag
   */
  protected $defaultPluginBag;

  /**
   * Stores all setup plugin instances.
   *
   * @var array
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

  public static function getInfo() {
    return array(
      'name' => 'Default plugin bag',
      'description' => 'Tests the default plugin bag.',
      'group' => 'PHP Storage',
    );
  }

  protected function setUp() {
    $this->pluginManager = $this->getMock('Drupal\Component\Plugin\PluginManagerInterface');
    $definitions = $this->getPluginDefinitions();
    $this->pluginManager->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $this->pluginInstances = array();
    $map = array();
    foreach ($definitions as $plugin_id => $definition) {
      // Create a mock plugin instance.
      $mock = $this->getMock('Drupal\Component\Plugin\PluginInspectionInterface');
      $mock->expects($this->any())
        ->method('getPluginId')
        ->will($this->returnValue($plugin_id));
      $this->pluginInstances[$plugin_id] = $mock;

      $map[] = array($plugin_id, $this->config[$plugin_id], $this->pluginInstances[$plugin_id]);
    }
    $this->pluginManager->expects($this->any())
      ->method('createInstance')
      ->will($this->returnValueMap($map));

    $this->defaultPluginBag = new DefaultPluginBag($this->pluginManager, $this->config);
  }

  /**
   * Tests the has method.
   *
   * @see \Drupal\Component\Plugin\DefaultPluginBag::has()
   */
  public function testHas() {
    $definitions = $this->getPluginDefinitions();

    $this->assertFalse($this->defaultPluginBag->has($this->randomName()), 'Nonexistent plugin found.');

    foreach (array_keys($definitions) as $plugin_id) {
      $this->assertTrue($this->defaultPluginBag->has($plugin_id));
    }
  }

  /**
   * Tests the get method.
   *
   * @see \Drupal\Component\Plugin\DefaultPluginBag::get()
   */
  public function testGet() {
    $apple = $this->pluginInstances['apple'];

    $this->assertEquals($apple, $this->defaultPluginBag->get('apple'));
  }

  /**
   * Tests the get method with an non existing plugin ID.
   *
   * @expectedException \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testGetNotExistingPlugin() {
    $this->defaultPluginBag->get('pear');
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

  /**
   * Provides test data for testSortHelper.
   *
   * @return array
   *   The test data.
   */
  public function providerTestSortHelper() {
    return array(
      array('apple', 'apple', 0),
      array('apple', 'cherry', -1),
      array('cherry', 'apple', 1),
      array('cherry', 'banana', 1),
    );
  }

  /**
   * Tests the sort helper.
   *
   * @param string $plugin_id_1
   *   The first plugin ID.
   * @param string $plugin_id_2
   *   The second plugin ID.
   * @param int $expected
   *   The expected result.
   *
   * @dataProvider providerTestSortHelper
   */
  public function testSortHelper($plugin_id_1, $plugin_id_2, $expected) {
    if ($expected != 0) {
      $expected = $expected > 0 ? 1 : -1;
    }
    $this->assertEquals($expected, $this->defaultPluginBag->sortHelper($plugin_id_1, $plugin_id_2));
  }

  /**
   * Tests the configuration getter method.
   *
   * @see \Drupal\Component\Plugin\DefaultPluginBag::getConfiguration()
   */
  public function testGetConfiguration() {
    // The expected order matches $this->config.
    $expected = array('banana', 'cherry', 'apple');

    $config = $this->defaultPluginBag->getConfiguration();
    $this->assertSame($expected, array_keys($config), 'The order of the configuration is unchanged.');

    $ids = $this->defaultPluginBag->getInstanceIDs();
    $this->assertSame($expected, array_keys($ids), 'The order of the instances is unchanged.');

    $this->defaultPluginBag->sort();
    $config = $this->defaultPluginBag->getConfiguration();
    $this->assertSame($expected, array_keys($config), 'After sorting, the order of the configuration is unchanged.');

    $ids = $this->defaultPluginBag->getInstanceIDs();
    sort($expected);
    $this->assertSame($expected, array_keys($ids), 'After sorting, the order of the instances is also sorted.');
  }

}
