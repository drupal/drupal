<?php

/**
 * @file
 * Contains \Drupal\views\Tests\ViewExecutableUnitTest.
 */

namespace Drupal\views\Tests;

use Drupal\Tests\UnitTestCase;
use Drupal\views\Entity\View;
use Drupal\views\ViewExecutable;

/**
 * @coversDefaultClass \Drupal\views\ViewExecutable
 * @group views
 */
class ViewExecutableUnitTest extends UnitTestCase {

  /**
   * Tests the buildThemeFunctions() method.
   */
  public function testBuildThemeFunctions() {
    $config = array(
      'id' => 'test_view',
      'tag' => 'OnE, TWO, and three',
      'display' => array(
        'default' => array(
          'id' => 'default',
          'display_plugin' => 'default',
          'display_title' => 'Default',
        ),
      ),
    );

    $storage = new View($config, 'view');
    $user = $this->getMock('Drupal\Core\Session\AccountInterface');
    $view = new ViewExecutable($storage, $user);

    $expected = array(
      'test_hook__test_view',
      'test_hook'
    );
    $this->assertEquals($expected, $view->buildThemeFunctions('test_hook'));

    // Add a mock display.
    $display = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();
    $display->display = $config['display']['default'];
    $view->display_handler = $display;

    $expected = array(
      'test_hook__test_view__default',
      'test_hook__default',
      'test_hook__one',
      'test_hook__two',
      'test_hook__and_three',
      'test_hook__test_view',
      'test_hook'
    );
    $this->assertEquals($expected, $view->buildThemeFunctions('test_hook'));

    //Change the name of the display plugin and make sure that is in the array.
    $view->display_handler->display['display_plugin'] = 'default2';

    $expected = array(
      'test_hook__test_view__default',
      'test_hook__default',
      'test_hook__one',
      'test_hook__two',
      'test_hook__and_three',
      'test_hook__test_view__default2',
      'test_hook__default2',
      'test_hook__test_view',
      'test_hook'
    );
    $this->assertEquals($expected, $view->buildThemeFunctions('test_hook'));
  }

}
