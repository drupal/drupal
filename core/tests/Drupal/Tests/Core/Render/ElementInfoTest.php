<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Render\ElementInfoTest.
 */

namespace Drupal\Tests\Core\Render;

use Drupal\Core\Render\ElementInfo;
use Drupal\Core\Render\ElementInfoInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the element info.
 *
 * @coversDefaultClass \Drupal\Core\Render\ElementInfo
 */
class ElementInfoTest extends UnitTestCase {

  /**
   * The class under test.
   *
   * @var \Drupal\Core\Render\ElementInfoInterface
   */
  protected $elementInfo;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Tests \Drupal\Core\Render\ElementInfo',
      'description' => '',
      'group' => 'Render',
    );
  }

  /**
   * {@inheritdoc}
   *
   * @covers ::__construct
   */
  protected function setUp() {
    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');

    $this->elementInfo = new ElementInfo($this->moduleHandler);
  }

  /**
   * Tests the getInfo method.
   *
   * @covers ::getInfo
   * @covers ::buildInfo
   *
   * @dataProvider providerTestGetInfo
   */
  public function testGetInfo($type, $expected_info, $element_info, callable $alter_callback = NULL) {
    $this->moduleHandler->expects($this->once())
      ->method('invokeAll')
      ->with('element_info')
      ->will($this->returnValue($element_info));
    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('element_info', $this->anything())
      ->will($this->returnCallback($alter_callback ?: function($info) {
        return $info;
      }));

    $this->assertEquals($expected_info, $this->elementInfo->getInfo($type));
  }

  /**
   * Provides tests data for getInfo.
   *
   * @return array
   */
  public function providerTestGetInfo() {
    $data = array();
    // Provide an element and expect it is returned.
    $data[] = array(
      'page',
      array(
        '#type' => 'page',
        '#show_messages' => TRUE,
        '#theme' => 'page',
      ),
      array('page' => array(
        '#show_messages' => TRUE,
        '#theme' => 'page',
      )),
    );
    // Provide an element but request an non existent one.
    $data[] = array(
      'form',
      array(
      ),
      array('page' => array(
        '#show_messages' => TRUE,
        '#theme' => 'page',
      )),
    );
    // Provide an element and alter it to ensure it is altered.
    $data[] = array(
      'page',
      array(
        '#type' => 'page',
        '#show_messages' => TRUE,
        '#theme' => 'page',
        '#number' => 597219,
      ),
      array('page' => array(
        '#show_messages' => TRUE,
        '#theme' => 'page',
      )),
      function ($alter_name, array &$info) {
        $info['page']['#number'] = 597219;
      }
    );
    return $data;
  }

}
