<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Plugin\DefaultPluginBagTest.
 */

namespace Drupal\Tests\Core\Plugin;

/**
 * Tests the default plugin bag.
 *
 * @see \Drupal\Core\Plugin\DefaultPluginBag
 *
 * @group Drupal
 * @group Drupal_Plugin
 */
class DefaultPluginBagTest extends PluginBagTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Default plugin bag',
      'description' => 'Tests the default plugin bag.',
      'group' => 'Plugin API',
    );
  }

  /**
   * Tests the has method.
   *
   * @see \Drupal\Core\Plugin\DefaultPluginBag::has()
   */
  public function testHas() {
    $this->setupPluginBag();
    $definitions = $this->getPluginDefinitions();

    $this->assertFalse($this->defaultPluginBag->has($this->randomName()), 'Nonexistent plugin found.');

    foreach (array_keys($definitions) as $plugin_id) {
      $this->assertTrue($this->defaultPluginBag->has($plugin_id));
    }
  }

  /**
   * Tests the get method.
   *
   * @see \Drupal\Core\Plugin\DefaultPluginBag::get()
   */
  public function testGet() {
    $this->setupPluginBag($this->once());
    $apple = $this->pluginInstances['apple'];

    $this->assertSame($apple, $this->defaultPluginBag->get('apple'));
  }

  /**
   * Tests the get method with an non existing plugin ID.
   *
   * @expectedException \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @expectedExceptionMessage Plugin ID 'pear' was not found.
   */
  public function testGetNotExistingPlugin() {
    $this->setupPluginBag();
    $this->defaultPluginBag->get('pear');
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
    $this->setupPluginBag($this->any());
    if ($expected != 0) {
      $expected = $expected > 0 ? 1 : -1;
    }
    $this->assertEquals($expected, $this->defaultPluginBag->sortHelper($plugin_id_1, $plugin_id_2));
  }

  /**
   * Tests the configuration getter method.
   *
   * @see \Drupal\Core\Plugin\DefaultPluginBag::getConfiguration()
   */
  public function testGetConfiguration() {
    $this->setupPluginBag($this->exactly(3));
    // The expected order matches $this->config.
    $expected = array('banana', 'cherry', 'apple');

    $config = $this->defaultPluginBag->getConfiguration();
    $this->assertSame($expected, array_keys($config), 'The order of the configuration is unchanged.');

    $ids = $this->defaultPluginBag->getInstanceIds();
    $this->assertSame($expected, array_keys($ids), 'The order of the instances is unchanged.');

    $this->defaultPluginBag->sort();
    $config = $this->defaultPluginBag->getConfiguration();
    $this->assertSame($expected, array_keys($config), 'After sorting, the order of the configuration is unchanged.');

    $ids = $this->defaultPluginBag->getInstanceIds();
    sort($expected);
    $this->assertSame($expected, array_keys($ids), 'After sorting, the order of the instances is also sorted.');
  }

  /**
   * Tests the addInstanceId() method.
   */
  public function testAddInstanceId() {
    $this->setupPluginBag($this->exactly(4));
    $expected = array(
      'banana' => 'banana',
      'cherry' => 'cherry',
      'apple' => 'apple',
    );
    $this->defaultPluginBag->addInstanceId('apple');
    $result = $this->defaultPluginBag->getInstanceIds();
    $this->assertSame($expected, $result);
    $this->assertSame($expected, array_intersect_key($result, $this->defaultPluginBag->getConfiguration()));

    $expected = array(
      'cherry' => 'cherry',
      'apple' => 'apple',
      'banana' => 'banana',
    );
    $this->defaultPluginBag->removeInstanceId('banana');
    $this->defaultPluginBag->addInstanceId('banana', $this->config['banana']);

    $result = $this->defaultPluginBag->getInstanceIds();
    $this->assertSame($expected, $result);
    $this->assertSame($expected, array_intersect_key($result, $this->defaultPluginBag->getConfiguration()));
  }

  /**
   * Tests the removeInstanceId() method.
   *
   * @see \Drupal\Core\Plugin\DefaultPluginBag::removeInstanceId()
   */
  public function testRemoveInstanceId() {
    $this->setupPluginBag($this->exactly(2));
    $this->defaultPluginBag->removeInstanceId('cherry');
    $config = $this->defaultPluginBag->getConfiguration();
    $this->assertArrayNotHasKey('cherry', $config, 'After removing an instance, the configuration is updated.');
  }

  /**
   * Tests the setInstanceConfiguration() method.
   *
   * @see \Drupal\Core\Plugin\DefaultPluginBag::setInstanceConfiguration()
   */
  public function testSetInstanceConfiguration() {
    $this->setupPluginBag($this->exactly(3));
    $expected = array(
      'id' => 'cherry',
      'key' => 'value',
      'custom' => 'bananas',
    );
    $this->defaultPluginBag->setInstanceConfiguration('cherry', $expected);
    $config = $this->defaultPluginBag->getConfiguration();
    $this->assertSame($expected, $config['cherry']);
  }

  /**
   * Tests the count() method.
   */
  public function testCount() {
    $this->setupPluginBag();
    $this->assertSame(3, $this->defaultPluginBag->count());
  }

  /**
   * Tests the clear() method.
   */
  public function testClear() {
    $this->setupPluginBag($this->exactly(6));
    $this->defaultPluginBag->getConfiguration();
    $this->defaultPluginBag->getConfiguration();
    $this->defaultPluginBag->clear();
    $this->defaultPluginBag->getConfiguration();
  }

  /**
   * Tests the set() method.
   */
  public function testSet() {
    $this->setupPluginBag($this->exactly(4));
    $instance = $this->pluginManager->createInstance('cherry', $this->config['cherry']);
    $this->defaultPluginBag->set('cherry2', $instance);
    $this->defaultPluginBag->setInstanceConfiguration('cherry2', $this->config['cherry']);

    $expected = array(
      'banana',
      'cherry',
      'apple',
      'cherry2',
    );
    $config = $this->defaultPluginBag->getConfiguration();
    $this->assertSame($expected, array_keys($config));
  }

}
