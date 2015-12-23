<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Handler\AreaMessagesTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\Tests\ViewKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the messages area handler.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\area\Messages
 */
class AreaMessagesTest extends ViewKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_area_messages');

  /**
   * Tests the messages area handler.
   */
  public function testMessageText() {
    drupal_set_message('My drupal set message.');

    $view = Views::getView('test_area_messages');

    $view->setDisplay('default');
    $this->executeView($view);
    $output = $view->render();
    $output = \Drupal::service('renderer')->renderRoot($output);
    $this->setRawContent($output);
    $this->assertText('My drupal set message.');
  }

}
