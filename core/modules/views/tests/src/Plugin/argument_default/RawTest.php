<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\argument_default\RawTest.
 */

namespace Drupal\views\Tests\Plugin\argument_default;

use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\argument_default\Raw;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the raw argument default plugin.
 *
 * @see \Drupal\views\Plugin\views\argument_default\Raw
 */
class RawTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Argument default: Raw',
      'description' => 'Tests the raw argument default plugin.',
      'group' => 'Views Plugin',
    );
  }

  /**
   * Test the getArgument() method.
   *
   * @see \Drupal\views\Plugin\views\argument_default\Raw::getArgument()
   */
  public function testGetArgument() {
    $view = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $display_plugin = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();

    $request = new Request(array(), array(), array('_system_path' => 'test/example'));
    $view->expects($this->any())
      ->method('getRequest')
      ->will($this->returnValue($request));
    $alias_manager = $this->getMock('Drupal\Core\Path\AliasManagerInterface');
    $alias_manager->expects($this->never())
      ->method('getAliasByPath');

    // Don't use aliases.
    $raw = new Raw(array(), 'raw', array(), $alias_manager);
    $options = array(
      'use_alias' => FALSE,
      'index' => 0,
    );
    $raw->init($view, $display_plugin, $options);
    $this->assertEquals('test', $raw->getArgument());

    $raw = new Raw(array(), 'raw', array(), $alias_manager);
    $options = array(
      'use_alias' => FALSE,
      'index' => 1,
    );
    $raw->init($view, $display_plugin, $options);
    $this->assertEquals('example', $raw->getArgument());

    // Setup an alias manager with a path alias.
    $alias_manager = $this->getMock('Drupal\Core\Path\AliasManagerInterface');
    $alias_manager->expects($this->any())
      ->method('getAliasByPath')
      ->with($this->equalTo('test/example'))
      ->will($this->returnValue('other/example'));

    $raw = new Raw(array(), 'raw', array(), $alias_manager);
    $options = array(
      'use_alias' => TRUE,
      'index' => 0,
    );
    $raw->init($view, $display_plugin, $options);
    $this->assertEquals('other', $raw->getArgument());

    $raw = new Raw(array(), 'raw', array(), $alias_manager);
    $options = array(
      'use_alias' => TRUE,
      'index' => 1,
    );
    $raw->init($view, $display_plugin, $options);
    $this->assertEquals('example', $raw->getArgument());

  }

}
