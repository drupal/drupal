<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Plugin\DefaultLazyPluginCollectionTest.
 */

namespace Drupal\Tests\Core\Plugin;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\DefaultLazyPluginCollection
 * @group Plugin
 */
class DefaultLazyPluginCollectionTest extends LazyPluginCollectionTestBase {

  /**
   * Tests the has method.
   *
   * @see \Drupal\Core\Plugin\DefaultLazyPluginCollection::has()
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
   * Tests the get method.
   *
   * @see \Drupal\Core\Plugin\DefaultLazyPluginCollection::get()
   */
  public function testGet() {
    $this->setupPluginCollection($this->once());
    $apple = $this->pluginInstances['apple'];

    $this->assertSame($apple, $this->defaultPluginCollection->get('apple'));
  }

  /**
   * Tests the get method with an non existing plugin ID.
   *
   * @expectedException \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @expectedExceptionMessage Plugin ID 'pear' was not found.
   */
  public function testGetNotExistingPlugin() {
    $this->setupPluginCollection();
    $this->defaultPluginCollection->get('pear');
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
    $this->setupPluginCollection($this->any());
    if ($expected != 0) {
      $expected = $expected > 0 ? 1 : -1;
    }
    $this->assertEquals($expected, $this->defaultPluginCollection->sortHelper($plugin_id_1, $plugin_id_2));
  }

  /**
   * Tests the configuration getter method.
   *
   * @see \Drupal\Core\Plugin\DefaultLazyPluginCollection::getConfiguration()
   */
  public function testGetConfiguration() {
    $this->setupPluginCollection($this->exactly(3));
    // The expected order matches $this->config.
    $expected = array('banana', 'cherry', 'apple');

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
   * Tests the addInstanceId() method.
   */
  public function testAddInstanceId() {
    $this->setupPluginCollection($this->exactly(4));
    $expected = array(
      'banana' => 'banana',
      'cherry' => 'cherry',
      'apple' => 'apple',
    );
    $this->defaultPluginCollection->addInstanceId('apple');
    $result = $this->defaultPluginCollection->getInstanceIds();
    $this->assertSame($expected, $result);
    $this->assertSame($expected, array_intersect_key($result, $this->defaultPluginCollection->getConfiguration()));

    $expected = array(
      'cherry' => 'cherry',
      'apple' => 'apple',
      'banana' => 'banana',
    );
    $this->defaultPluginCollection->removeInstanceId('banana');
    $this->defaultPluginCollection->addInstanceId('banana', $this->config['banana']);

    $result = $this->defaultPluginCollection->getInstanceIds();
    $this->assertSame($expected, $result);
    $this->assertSame($expected, array_intersect_key($result, $this->defaultPluginCollection->getConfiguration()));
  }

  /**
   * Tests the removeInstanceId() method.
   *
   * @see \Drupal\Core\Plugin\DefaultLazyPluginCollection::removeInstanceId()
   */
  public function testRemoveInstanceId() {
    $this->setupPluginCollection($this->exactly(2));
    $this->defaultPluginCollection->removeInstanceId('cherry');
    $config = $this->defaultPluginCollection->getConfiguration();
    $this->assertArrayNotHasKey('cherry', $config, 'After removing an instance, the configuration is updated.');
  }

  /**
   * Tests the setInstanceConfiguration() method.
   *
   * @see \Drupal\Core\Plugin\DefaultLazyPluginCollection::setInstanceConfiguration()
   */
  public function testSetInstanceConfiguration() {
    $this->setupPluginCollection($this->exactly(3));
    $expected = array(
      'id' => 'cherry',
      'key' => 'value',
      'custom' => 'bananas',
    );
    $this->defaultPluginCollection->setInstanceConfiguration('cherry', $expected);
    $config = $this->defaultPluginCollection->getConfiguration();
    $this->assertSame($expected, $config['cherry']);
  }

  /**
   * Tests the count() method.
   */
  public function testCount() {
    $this->setupPluginCollection();
    $this->assertSame(3, $this->defaultPluginCollection->count());
  }

  /**
   * Tests the clear() method.
   */
  public function testClear() {
    $this->setupPluginCollection($this->exactly(6));
    $this->defaultPluginCollection->getConfiguration();
    $this->defaultPluginCollection->getConfiguration();
    $this->defaultPluginCollection->clear();
    $this->defaultPluginCollection->getConfiguration();
  }

  /**
   * Tests the set() method.
   */
  public function testSet() {
    $this->setupPluginCollection($this->exactly(4));
    $instance = $this->pluginManager->createInstance('cherry', $this->config['cherry']);
    $this->defaultPluginCollection->set('cherry2', $instance);
    $this->defaultPluginCollection->setInstanceConfiguration('cherry2', $this->config['cherry']);

    $expected = array(
      'banana',
      'cherry',
      'apple',
      'cherry2',
    );
    $config = $this->defaultPluginCollection->getConfiguration();
    $this->assertSame($expected, array_keys($config));
  }

}
