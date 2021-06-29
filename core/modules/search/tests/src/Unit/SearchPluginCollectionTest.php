<?php

namespace Drupal\Tests\search\Unit;

use Drupal\search\Plugin\SearchPluginCollection;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\search\Plugin\SearchPluginCollection
 * @group search
 */
class SearchPluginCollectionTest extends UnitTestCase {

  /**
   * The mocked plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $pluginManager;

  /**
   * The tested plugin collection.
   *
   * @var \Drupal\search\Plugin\SearchPluginCollection
   */
  protected $searchPluginCollection;

  /**
   * Stores all setup plugin instances.
   *
   * @var \Drupal\search\Plugin\SearchInterface[]
   */
  protected $pluginInstances;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->pluginManager = $this->createMock('Drupal\Component\Plugin\PluginManagerInterface');
    $this->searchPluginCollection = new SearchPluginCollection($this->pluginManager, 'banana', ['id' => 'banana', 'color' => 'yellow'], 'fruit_stand');
  }

  /**
   * Tests the get() method.
   */
  public function testGet() {
    $plugin = $this->createMock('Drupal\search\Plugin\SearchInterface');
    $this->pluginManager->expects($this->once())
      ->method('createInstance')
      ->willReturn($plugin);
    $this->assertSame($plugin, $this->searchPluginCollection->get('banana'));
  }

  /**
   * Tests the get() method with a configurable plugin.
   */
  public function testGetWithConfigurablePlugin() {
    $plugin = $this->createMock('Drupal\search\Plugin\ConfigurableSearchPluginInterface');
    $plugin->expects($this->once())
      ->method('setSearchPageId')
      ->with('fruit_stand')
      ->willReturn($plugin);

    $this->pluginManager->expects($this->once())
      ->method('createInstance')
      ->willReturn($plugin);

    $this->assertSame($plugin, $this->searchPluginCollection->get('banana'));
  }

}
