<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Menu\StaticMenuLinkOverridesTest.
 */

namespace Drupal\Tests\Core\Menu;

use Drupal\Core\Menu\StaticMenuLinkOverrides;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Menu\StaticMenuLinkOverrides
 * @group Menu
 */
class StaticMenuLinkOverridesTest extends UnitTestCase {

  /**
   * Tests the constructor.
   *
   * @covers ::__construct
   */
  public function testConstruct() {
    $config_factory = $this->getConfigFactoryStub(array('core.menu.static_menu_link_overrides' => array()));
    $static_override = new StaticMenuLinkOverrides($config_factory);

    $this->assertAttributeEquals($config_factory, 'configFactory', $static_override);
  }

  /**
   * Tests the reload method.
   *
   * @covers ::reload
   */
  public function testReload() {
    $config_factory = $this->getMock('Drupal\Core\Config\ConfigFactoryInterface');
    $config_factory->expects($this->at(0))
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
    $config_factory = $this->getConfigFactoryStub(array('core.menu.static_menu_link_overrides' => array('definitions' => $overrides)));
    $static_override = new StaticMenuLinkOverrides($config_factory);

    $this->assertEquals($expected, $static_override->loadOverride($id));
  }

  /**
   * Provides test data for testLoadOverride.
   */
  public function providerTestLoadOverride() {
    $data = array();
    // No specified ID.
    $data[] = array(array('test1' => array('parent' => 'test0')), NULL, array());
    // Valid ID.
    $data[] = array(array('test1' => array('parent' => 'test0')), 'test1', array('parent' => 'test0'));
    // Non existing ID.
    $data[] = array(array('test1' => array('parent' => 'test0')), 'test2', array());
    // Ensure that the ID is encoded properly
    $data[] = array(array('test1__la___ma' => array('parent' => 'test0')), 'test1.la__ma', array('parent' => 'test0'));

    return $data;
  }

  /**
   * Tests the loadMultipleOverrides method.
   *
   * @covers ::loadMultipleOverrides
   * @covers ::getConfig
   */
  public function testLoadMultipleOverrides() {
    $overrides = array();
    $overrides['test1'] = array('parent' => 'test0');
    $overrides['test2'] = array('parent' => 'test1');
    $overrides['test1__la___ma'] = array('parent' => 'test2');

    $config_factory = $this->getConfigFactoryStub(array('core.menu.static_menu_link_overrides' => array('definitions' => $overrides)));
    $static_override = new StaticMenuLinkOverrides($config_factory);

    $this->assertEquals(array('test1' => array('parent' => 'test0'), 'test1.la__ma' => array('parent' => 'test2')), $static_override->loadMultipleOverrides(array('test1', 'test1.la__ma')));
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
    $config->expects($this->at(0))
      ->method('get')
      ->with('definitions')
      ->will($this->returnValue(array()));
    $config->expects($this->at(1))
      ->method('get')
      ->with('definitions')
      ->will($this->returnValue(array()));

    $definition_save_1 = array('definitions' => array('test1' => array('parent' => 'test0')));
    $definitions_save_2 = array(
      'definitions' => array(
        'test1' => array('parent' => 'test0'),
        'test1__la___ma' => array('parent' => 'test1')
      )
    );
    $config->expects($this->at(2))
      ->method('set')
      ->with('definitions', $definition_save_1['definitions'])
      ->will($this->returnSelf());
    $config->expects($this->at(3))
      ->method('save');
    $config->expects($this->at(4))
      ->method('get')
      ->with('definitions')
      ->will($this->returnValue($definition_save_1['definitions']));
    $config->expects($this->at(5))
      ->method('get')
      ->with('definitions')
      ->will($this->returnValue($definition_save_1['definitions']));
    $config->expects($this->at(6))
      ->method('set')
      ->with('definitions', $definitions_save_2['definitions'])
      ->will($this->returnSelf());
    $config->expects($this->at(7))
      ->method('save');

    $config_factory = $this->getMock('Drupal\Core\Config\ConfigFactoryInterface');
    $config_factory->expects($this->once())
      ->method('getEditable')
      ->will($this->returnValue($config));

    $static_override = new StaticMenuLinkOverrides($config_factory);

    $static_override->saveOverride('test1', array('parent' => 'test0'));
    $static_override->saveOverride('test1.la__ma', array('parent' => 'test1'));
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
    $config->expects($this->at(0))
      ->method('get')
      ->with('definitions')
      ->will($this->returnValue($old_definitions));

    // Only save if the definitions changes.
    if ($old_definitions != $new_definitions) {
      $config->expects($this->at(1))
        ->method('set')
        ->with('definitions', $new_definitions)
        ->will($this->returnSelf());
      $config->expects($this->at(2))
        ->method('save');
    }

    $config_factory = $this->getMock('Drupal\Core\Config\ConfigFactoryInterface');
    $config_factory->expects($this->once())
      ->method('getEditable')
      ->will($this->returnValue($config));

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
    $data = array();
    // Delete a non existing ID.
    $data[] = array('test0', array(), array());
    // Delete an existing ID.
    $data[] = array('test1', array('test1' => array('parent' => 'test0')), array());
    // Delete an existing ID with a special ID.
    $data[] = array('test1.la__ma', array('test1__la___ma' => array('parent' => 'test0')), array());
    // Delete multiple IDs.
    $data[] = array(array('test1.la__ma', 'test1'), array('test1' => array('parent' => 'test0'), 'test1__la___ma' => array('parent' => 'test0')), array());

    return $data;
  }

}
