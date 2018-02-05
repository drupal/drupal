<?php

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\layout_builder\SectionListInterface;
use Drupal\layout_builder\SectionStorage\SectionStorageManager;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\layout_builder\SectionStorage\SectionStorageManager
 *
 * @group layout_builder
 */
class SectionStorageManagerTest extends UnitTestCase {

  /**
   * The section storage manager.
   *
   * @var \Drupal\layout_builder\SectionStorage\SectionStorageManager
   */
  protected $manager;

  /**
   * The plugin.
   *
   * @var \Drupal\layout_builder\SectionStorageInterface
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $cache = $this->prophesize(CacheBackendInterface::class);
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $this->manager = new SectionStorageManager(new \ArrayObject(), $cache->reveal(), $module_handler->reveal());

    $this->plugin = $this->prophesize(SectionStorageInterface::class);

    $factory = $this->prophesize(FactoryInterface::class);
    $factory->createInstance('the_plugin_id', [])->willReturn($this->plugin->reveal());
    $reflection_property = new \ReflectionProperty($this->manager, 'factory');
    $reflection_property->setAccessible(TRUE);
    $reflection_property->setValue($this->manager, $factory->reveal());
  }

  /**
   * @covers ::loadEmpty
   */
  public function testLoadEmpty() {
    $result = $this->manager->loadEmpty('the_plugin_id');
    $this->assertInstanceOf(SectionStorageInterface::class, $result);
  }

  /**
   * @covers ::loadFromStorageId
   */
  public function testLoadFromStorageId() {
    $section_list = $this->prophesize(SectionListInterface::class);
    $this->plugin->setSectionList($section_list->reveal())->will(function () {
      return $this;
    });
    $this->plugin->getSectionListFromId('the_storage_id')->willReturn($section_list->reveal());

    $result = $this->manager->loadFromStorageId('the_plugin_id', 'the_storage_id');
    $this->assertInstanceOf(SectionStorageInterface::class, $result);
  }

  /**
   * @covers ::loadFromRoute
   */
  public function testLoadFromRoute() {
    $section_list = $this->prophesize(SectionListInterface::class);
    $this->plugin->extractIdFromRoute('the_value', [], 'the_parameter_name', [])->willReturn('the_storage_id');
    $this->plugin->getSectionListFromId('the_storage_id')->willReturn($section_list->reveal());
    $this->plugin->setSectionList($section_list->reveal())->will(function () {
      return $this;
    });

    $result = $this->manager->loadFromRoute('the_plugin_id', 'the_value', [], 'the_parameter_name', []);
    $this->assertInstanceOf(SectionStorageInterface::class, $result);
  }

  /**
   * @covers ::loadFromRoute
   */
  public function testLoadFromRouteNull() {
    $this->plugin->extractIdFromRoute('the_value', [], 'the_parameter_name', ['_route' => 'the_route_name'])->willReturn(NULL);

    $result = $this->manager->loadFromRoute('the_plugin_id', 'the_value', [], 'the_parameter_name', ['_route' => 'the_route_name']);
    $this->assertNull($result);
  }

}
