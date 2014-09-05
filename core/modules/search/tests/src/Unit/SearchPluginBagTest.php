<?php

/**
 * @file
 * Contains \Drupal\Tests\search\Unit\SearchPluginBagTest.
 */

namespace Drupal\Tests\search\Unit;

use Drupal\search\Plugin\SearchPluginBag;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\search\Plugin\SearchPluginBag
 * @group search
 */
class SearchPluginBagTest extends UnitTestCase {

  /**
   * The mocked plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $pluginManager;

  /**
   * The tested plugin bag.
   *
   * @var \Drupal\search\Plugin\SearchPluginBag
   */
  protected $searchPluginBag;

  /**
   * Stores all setup plugin instances.
   *
   * @var \Drupal\search\Plugin\SearchInterface[]
   */
  protected $pluginInstances;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->pluginManager = $this->getMock('Drupal\Component\Plugin\PluginManagerInterface');
    $this->searchPluginBag = new SearchPluginBag($this->pluginManager, 'banana', array('id' => 'banana', 'color' => 'yellow'), 'fruit_stand');
  }

  /**
   * Tests the get() method.
   */
  public function testGet() {
    $plugin = $this->getMock('Drupal\search\Plugin\SearchInterface');
    $this->pluginManager->expects($this->once())
      ->method('createInstance')
      ->will($this->returnValue($plugin));
    $this->assertSame($plugin, $this->searchPluginBag->get('banana'));
  }

  /**
   * Tests the get() method with a configurable plugin.
   */
  public function testGetWithConfigurablePlugin() {
    $plugin = $this->getMock('Drupal\search\Plugin\ConfigurableSearchPluginInterface');
    $plugin->expects($this->once())
      ->method('setSearchPageId')
      ->with('fruit_stand')
      ->will($this->returnValue($plugin));

    $this->pluginManager->expects($this->once())
      ->method('createInstance')
      ->will($this->returnValue($plugin));

    $this->assertSame($plugin, $this->searchPluginBag->get('banana'));
  }

}
