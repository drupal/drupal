<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Plugin\PluginBaseTest.
 */

namespace Drupal\Tests\Component\Plugin;

use Drupal\Component\PhpStorage\PhpStorageFactory;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the plugin base.
 *
 * @group Drupal
 *
 * @see \Drupal\Component\Plugin\PluginBase
 */
class PluginBaseTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Plugin base test',
      'description' => 'Tests the plugin base',
      'group' => 'Plugin',
    );
  }

  /**
   * Tests the getPluginId method.
   *
   * @dataProvider providerTestGetPluginId
   *
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   *
   */
  public function testGetPluginId($plugin_id, $expected) {
    $plugin_base = $this->getMockForAbstractClass('Drupal\Component\Plugin\PluginBase', array(
      array(),
      $plugin_id,
      array(),
    ));

    $this->assertEquals($expected, $plugin_base->getPluginId());
  }

  /**
   * Returns test data for testGetPluginId().
   *
   * @return array
   */
  public function providerTestGetPluginId() {
    return array(
      array('base_id', 'base_id'),
      array('base_id:derivative', 'base_id:derivative'),
    );
  }

  /**
   * Tests the getBasePluginId method.
   *
   * @dataProvider providerTestGetBasePluginId
   *
   * @see \Drupal\Component\Plugin\PluginBase::getBasePluginId()
   */
  public function testGetBasePluginId($plugin_id, $expected) {
    $plugin_base = $this->getMockForAbstractClass('Drupal\Component\Plugin\PluginBase', array(
      array(),
      $plugin_id,
      array(),
    ));

    $this->assertEquals($expected, $plugin_base->getBasePluginId());
  }

  /**
   * Returns test data for testGetBasePluginId().
   *
   * @return array
   */
  public function providerTestGetBasePluginId() {
    return array(
      array('base_id', 'base_id'),
      array('base_id:derivative', 'base_id'),
    );
  }


  /**
   * Tests the getBasePluginId method.
   *
   * @dataProvider providerTestGetDerivativeId
   *
   * @see \Drupal\Component\Plugin\PluginBase::getBasePluginId()
   */
  public function testGetDerivativeId($plugin_id = NULL, $expected = NULL) {
    $plugin_base = $this->getMockForAbstractClass('Drupal\Component\Plugin\PluginBase', array(
      array(),
      $plugin_id,
      array(),
    ));

    $this->assertEquals($expected, $plugin_base->getDerivativeId());
  }

  /**
   * Returns test data for testGetBasePluginId().
   *
   * @return array
   */
  public function providerTestGetDerivativeId() {
    return array(
      array('base_id', NULL),
      array('base_id:derivative', 'derivative'),
    );
  }

  /**
   * Tests the getPluginDefinition method.
   *
   * @see \Drupal\Component\Plugin\PluginBase::getPluginDefinition()
   */
  public function testGetPluginDefinition() {
    $plugin_base = $this->getMockForAbstractClass('Drupal\Component\Plugin\PluginBase', array(
      array(),
      'plugin_id',
      array('value', array('key' => 'value')),
    ));

    $this->assertEquals(array('value', array('key' => 'value')), $plugin_base->getPluginDefinition());
  }

}
