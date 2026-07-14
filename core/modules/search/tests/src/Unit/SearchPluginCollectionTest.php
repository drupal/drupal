<?php

declare(strict_types=1);

namespace Drupal\Tests\search\Unit;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\search\Plugin\SearchInterface;
use Drupal\search\Plugin\SearchPluginCollection;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests Drupal\search\Plugin\SearchPluginCollection.
 */
#[CoversClass(SearchPluginCollection::class)]
#[Group('search')]
class SearchPluginCollectionTest extends UnitTestCase {

  /**
   * The mocked plugin manager.
   */
  protected PluginManagerInterface&MockObject $pluginManager;

  /**
   * The tested plugin collection.
   *
   * @var \Drupal\search\Plugin\SearchPluginCollection
   */
  protected $searchPluginCollection;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->pluginManager = $this->createMock('Drupal\Component\Plugin\PluginManagerInterface');
    $this->searchPluginCollection = new SearchPluginCollection(
      $this->pluginManager,
      'banana',
      ['id' => 'banana', 'color' => 'yellow'],
      'fruit_stand');
  }

  /**
   * Tests the get() method.
   */
  public function testGet(): void {
    $plugin = $this->createStub(SearchInterface::class);
    $this->pluginManager->expects($this->once())
      ->method('createInstance')
      ->willReturn($plugin);
    $this->assertSame($plugin, $this->searchPluginCollection->get('banana'));
  }

  /**
   * Tests the get() method with a configurable plugin.
   */
  public function testGetWithConfigurablePlugin(): void {
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
