<?php

/**
 * @file
 * Contains \Drupal\Tests\views\Unit\ViewsHandlerManagerTest.
 */

namespace Drupal\Tests\views\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\ViewsHandlerManager;

/**
 * Tests the ViewsHandlerManager class.
 *
 * @group views
 *
 * @coversDefaultClass \Drupal\views\Plugin\ViewsHandlerManager
 */
class ViewsHandlerManagerTest extends UnitTestCase {

  /**
   * @var \Drupal\views\Plugin\ViewsHandlerManager
   */
  protected $handlerManager;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $views_data = $this->getMockBuilder('Drupal\views\ViewsData')
      ->disableOriginalConstructor()
      ->getMock();
    $cache_backend = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->handlerManager = new ViewsHandlerManager('test', new \ArrayObject(array()), $views_data, $cache_backend, $this->moduleHandler);
  }

  /**
   * Tests that hook_views_plugins_TYPE_alter() is invoked for a handler type.
   *
   * @covers ::__construct
   * @covers ::getDefinitions
   */
  public function testAlterHookInvocation() {
    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('views_plugins_test', array());

    $this->handlerManager->getDefinitions();
  }

}
