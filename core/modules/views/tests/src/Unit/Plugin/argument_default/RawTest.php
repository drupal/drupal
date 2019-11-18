<?php

namespace Drupal\Tests\views\Unit\Plugin\argument_default;

use Drupal\Core\Path\CurrentPathStack;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\argument_default\Raw;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\views\Plugin\views\argument_default\Raw
 * @group views
 */
class RawTest extends UnitTestCase {

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
    $current_path = new CurrentPathStack(new RequestStack());

    $request = new Request();
    $current_path->setPath('/test/example', $request);
    $view->expects($this->any())
      ->method('getRequest')
      ->will($this->returnValue($request));
    $alias_manager = $this->createMock(AliasManagerInterface::class);
    $alias_manager->expects($this->never())
      ->method('getAliasByPath');

    // Don't use aliases. Check against NULL and nonexistent path component
    // values in addition to valid ones.
    $raw = new Raw([], 'raw', [], $alias_manager, $current_path);
    $options = [
      'use_alias' => FALSE,
    ];
    $raw->init($view, $display_plugin, $options);
    $this->assertEquals(NULL, $raw->getArgument());

    $raw = new Raw([], 'raw', [], $alias_manager, $current_path);
    $options = [
      'use_alias' => FALSE,
      'index' => 0,
    ];
    $raw->init($view, $display_plugin, $options);
    $this->assertEquals('test', $raw->getArgument());

    $raw = new Raw([], 'raw', [], $alias_manager, $current_path);
    $options = [
      'use_alias' => FALSE,
      'index' => 1,
    ];
    $raw->init($view, $display_plugin, $options);
    $this->assertEquals('example', $raw->getArgument());

    $raw = new Raw([], 'raw', [], $alias_manager, $current_path);
    $options = [
      'use_alias' => FALSE,
      'index' => 2,
    ];
    $raw->init($view, $display_plugin, $options);
    $this->assertEquals(NULL, $raw->getArgument());

    // Setup an alias manager with a path alias.
    $alias_manager = $this->createMock(AliasManagerInterface::class);
    $alias_manager->expects($this->any())
      ->method('getAliasByPath')
      ->with($this->equalTo('/test/example'))
      ->will($this->returnValue('/other/example'));

    $raw = new Raw([], 'raw', [], $alias_manager, $current_path);
    $options = [
      'use_alias' => TRUE,
    ];
    $raw->init($view, $display_plugin, $options);
    $this->assertEquals(NULL, $raw->getArgument());

    $raw = new Raw([], 'raw', [], $alias_manager, $current_path);
    $options = [
      'use_alias' => TRUE,
      'index' => 0,
    ];
    $raw->init($view, $display_plugin, $options);
    $this->assertEquals('other', $raw->getArgument());

    $raw = new Raw([], 'raw', [], $alias_manager, $current_path);
    $options = [
      'use_alias' => TRUE,
      'index' => 1,
    ];
    $raw->init($view, $display_plugin, $options);
    $this->assertEquals('example', $raw->getArgument());

    $raw = new Raw([], 'raw', [], $alias_manager, $current_path);
    $options = [
      'use_alias' => TRUE,
      'index' => 2,
    ];
    $raw->init($view, $display_plugin, $options);
    $this->assertEquals(NULL, $raw->getArgument());
  }

}
