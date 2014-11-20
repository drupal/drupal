<?php

/**
 * @file
 * Contains \Drupal\Tests\views\Unit\Plugin\area\MessagesTest.
 */

namespace Drupal\Tests\views\Unit\Plugin\area;

use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\area\Messages;

/**
 * @coversDefaultClass \Drupal\views\Plugin\views\area\Messages
 * @group views
 */
class MessagesTest extends UnitTestCase {

  /**
   * The view executable object.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $view;

  /**
   * The message handler.
   *
   * @var \Drupal\views\Plugin\views\area\Messages
   */
  protected $messagesHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->messagesHandler = new Messages(array(), 'result', array());
  }

  /**
   * Tests the render method.
   *
   * @covers ::defineOptions
   * @covers ::render
   */
  public function testRender() {
    // The handler is configured to show with empty views by default, so should
    // appear.
    $this->assertSame(array('#theme' => 'status_messages'), $this->messagesHandler->render());

    // Turn empty off, and make sure it isn't rendered.
    $this->messagesHandler->options['empty'] = FALSE;
    // $empty parameter passed to render will still be FALSE, so should still
    // appear.
    $this->assertSame(array('#theme' => 'status_messages'), $this->messagesHandler->render());
    // Should now be empty as both the empty option and parameter are empty.
    $this->assertSame(array(), $this->messagesHandler->render(TRUE));
  }

}
