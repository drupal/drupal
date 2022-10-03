<?php

namespace Drupal\Tests\Core\Menu;

use Drupal\Core\Menu\StaticMenuLinkOverrides;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Menu\StaticMenuLinkOverrides
 * @group Menu
 */
class StaticMenuLinkOverridesTest extends UnitTestCase {

  /**
   * Tests the reload method.
   *
   * @covers ::reload
   */
  public function testReload() {
    $config_factory = $this->createMock('Drupal\Core\Config\ConfigFactoryInterface');
    $config_factory->expects($this->once())
      ->method('reset')
      ->with('core.menu.static_menu_link_overrides');

    $static_override = new StaticMenuLinkOverrides($config_factory);

    $static_override->reload();
  }

  /**
   * Tests the loadOverride method.
   *
   * @dataProvider providerTestLoadOverride
   *
   * @covers ::loadOverride
   * @covers ::getConfig
   */
  public function testLoadOverride($overrides, $id, $expected) {
    $config_factory = $this->getConfigFactoryStub(['core.menu.static_menu_link_overrides' => ['definitions' => $overrides]]);
    $static_override = new StaticMenuLinkOverrides($config_factory);

    $this->assertEquals($expected, $static_override->loadOverride($id));
  }

  /**
   * Provides test data for testLoadOverride.
   */
  public function providerTestLoadOverride() {
    $data = [];
    // Valid ID.
    $data[] = [['test1' => ['parent' => 'test0']], 'test1', ['parent' => 'test0']];
    // Non existing ID.
    $data[] = [['test1' => ['parent' => 'test0']], 'test2', []];
    // Ensure that the ID is encoded properly
    $data[] = [['test1__la___ma' => ['parent' => 'test0']], 'test1.la__ma', ['parent' => 'test0']];

    return $data;
  }

  /**
   * Tests the loadMultipleOverrides method.
   *
   * @covers ::loadMultipleOverrides
   * @covers ::getConfig
   */
  public function testLoadMultipleOverrides() {
    $overrides = [];
    $overrides['test1'] = ['parent' => 'test0'];
    $overrides['test2'] = ['parent' => 'test1'];
    $overrides['test1__la___ma'] = ['parent' => 'test2'];

    $config_factory = $this->getConfigFactoryStub(['core.menu.static_menu_link_overrides' => ['definitions' => $overrides]]);
    $static_override = new StaticMenuLinkOverrides($config_factory);

    $this->assertEquals(['test1' => ['parent' => 'test0'], 'test1.la__ma' => ['parent' => 'test2']], $static_override->loadMultipleOverrides(['test1', 'test1.la__ma']));
  }

  /**
   * Tests the saveOverride method.
   *
   * @covers ::saveOverride
   * @covers ::loadOverride
   * @covers ::getConfig
   */
  public function testSaveOverride() {
    $config = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();

    $definition_save_1 = [
      'definitions' => [
        'test1' => ['parent' => 'test0', 'menu_name' => '', 'weight' => 0, 'expanded' => FALSE, 'enabled' => FALSE],
      ],
    ];
    $definitions_save_2 = [
      'definitions' => [
        'test1' => ['parent' => 'test0', 'menu_name' => '', 'weight' => 0, 'expanded' => FALSE, 'enabled' => FALSE],
        'test1__la___ma' => ['parent' => 'test1', 'menu_name' => '', 'weight' => 0, 'expanded' => FALSE, 'enabled' => FALSE],
      ],
    ];

    $config->expects($this->exactly(4))
      ->method('get')
      ->with('definitions')
      ->willReturnOnConsecutiveCalls(
        [],
        [],
        $definition_save_1['definitions'],
        $definition_save_1['definitions'],
      );
    $config->expects($this->exactly(2))
      ->method('set')
      ->withConsecutive(
        ['definitions', $definition_save_1['definitions']],
        ['definitions', $definitions_save_2['definitions']],
      )
      ->will($this->returnSelf());
    $config->expects($this->exactly(2))
      ->method('save');

    $config_factory = $this->createMock('Drupal\Core\Config\ConfigFactoryInterface');
    $config_factory->expects($this->once())
      ->method('getEditable')
      ->willReturn($config);

    $static_override = new StaticMenuLinkOverrides($config_factory);

    $static_override->saveOverride('test1', ['parent' => 'test0']);
    $static_override->saveOverride('test1.la__ma', ['parent' => 'test1']);
  }

  /**
   * Tests the deleteOverride and deleteOverrides method.
   *
   * @param array|string $ids
   *   Either a single ID or multiple ones as array.
   * @param array $old_definitions
   *   The definitions before the deleting
   * @param array $new_definitions
   *   The definitions after the deleting.
   *
   * @dataProvider providerTestDeleteOverrides
   */
  public function testDeleteOverrides($ids, array $old_definitions, array $new_definitions) {
    $config = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config->expects($this->once())
      ->method('get')
      ->with('definitions')
      ->willReturn($old_definitions);

    // Only save if the definitions changes.
    $config->expects($old_definitions != $new_definitions ? $this->once() : $this->never())
      ->method('set')
      ->with('definitions', $new_definitions)
      ->will($this->returnSelf());
    $config->expects($old_definitions != $new_definitions ? $this->once() : $this->never())
      ->method('save');

    $config_factory = $this->createMock('Drupal\Core\Config\ConfigFactoryInterface');
    $config_factory->expects($this->once())
      ->method('getEditable')
      ->willReturn($config);

    $static_override = new StaticMenuLinkOverrides($config_factory);

    if (is_array($ids)) {
      $static_override->deleteMultipleOverrides($ids);
    }
    else {
      $static_override->deleteOverride($ids);
    }
  }

  /**
   * Provides test data for testDeleteOverrides.
   */
  public function providerTestDeleteOverrides() {
    $data = [];
    // Delete a non existing ID.
    $data[] = ['test0', [], []];
    // Delete an existing ID.
    $data[] = ['test1', ['test1' => ['parent' => 'test0']], []];
    // Delete an existing ID with a special ID.
    $data[] = ['test1.la__ma', ['test1__la___ma' => ['parent' => 'test0']], []];
    // Delete multiple IDs.
    $data[] = [['test1.la__ma', 'test1'], ['test1' => ['parent' => 'test0'], 'test1__la___ma' => ['parent' => 'test0']], []];

    return $data;
  }

}
