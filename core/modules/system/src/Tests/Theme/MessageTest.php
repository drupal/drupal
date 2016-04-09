<?php

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
    \Drupal::service('theme_handler')->setDefault('classy');

    drupal_set_message('An error occurred', 'error');
    drupal_set_message('But then something nice happened');
    $messages = array(
      '#type' => 'status_messages',
    );
    $this->render($messages);
    $this->assertRaw('messages messages--error');
    $this->assertRaw('messages messages--status');
  }
}
