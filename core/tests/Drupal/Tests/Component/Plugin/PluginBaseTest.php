<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Plugin\PluginBaseTest.
 */

namespace Drupal\Tests\Component\Plugin;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Component\Plugin\PluginBase
 * @group Plugin
 */
class PluginBaseTest extends UnitTestCase {

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
   * Tests the getBaseId method.
   *
   * @dataProvider providerTestGetBaseId
   *
   * @see \Drupal\Component\Plugin\PluginBase::getBaseId()
   */
  public function testGetBaseId($plugin_id, $expected) {
    /** @var \Drupal\Component\Plugin\PluginBase|\PHPUnit_Framework_MockObject_MockObject $plugin_base */
    $plugin_base = $this->getMockForAbstractClass('Drupal\Component\Plugin\PluginBase', array(
      array(),
      $plugin_id,
      array(),
    ));

    $this->assertEquals($expected, $plugin_base->getBaseId());
  }

  /**
   * Returns test data for testGetBaseId().
   *
   * @return array
   */
  public function providerTestGetBaseId() {
    return array(
      array('base_id', 'base_id'),
      array('base_id:derivative', 'base_id'),
    );
  }


  /**
   * Tests the getDerivativeId method.
   *
   * @dataProvider providerTestGetDerivativeId
   *
   * @see \Drupal\Component\Plugin\PluginBase::getDerivativeId()
   */
  public function testGetDerivativeId($plugin_id = NULL, $expected = NULL) {
    /** @var \Drupal\Component\Plugin\PluginBase|\PHPUnit_Framework_MockObject_MockObject $plugin_base */
    $plugin_base = $this->getMockForAbstractClass('Drupal\Component\Plugin\PluginBase', array(
      array(),
      $plugin_id,
      array(),
    ));

    $this->assertEquals($expected, $plugin_base->getDerivativeId());
  }

  /**
   * Returns test data for testGetDerivativeId().
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
