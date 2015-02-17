<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\MessageTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\KernelTestBase;

/**
 * Tests built-in message theme functions.
 *
 * @group Theme
 */
class MessageTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('system');

  /**
   * Tests setting messages output.
   */
  function testMessages() {
    // Enable the Classy theme.
    \Drupal::service('theme_handler')->install(['classy']);
    $this->config('system.theme')->set('default', 'classy')->save();

    drupal_set_message('An error occurred', 'error');
    drupal_set_message('But then something nice happened');
    $messages = array(
      '#theme' => 'status_messages',
    );
    $this->render($messages);
    $this->assertRaw('messages messages--error');
    $this->assertRaw('messages messages--status');
  }
}
