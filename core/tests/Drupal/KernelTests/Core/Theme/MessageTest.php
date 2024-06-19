<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Theme;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests built-in message theme functions.
 *
 * @group Theme
 */
class MessageTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Tests setting messages output.
   */
  public function testMessages(): void {
    // Enable the Starterkit theme.
    \Drupal::service('theme_installer')->install(['starterkit_theme']);
    $this->config('system.theme')->set('default', 'starterkit_theme')->save();

    \Drupal::messenger()->addError('An error occurred');
    \Drupal::messenger()->addStatus('But then something nice happened');
    $messages = [
      '#type' => 'status_messages',
    ];
    $this->render($messages);
    $this->assertRaw('messages messages--error');
    $this->assertRaw('messages messages--status');
    // Tests display of only one type of messages.
    \Drupal::messenger()->addError('An error occurred');
    $messages = [
      '#type' => 'status_messages',
      '#display' => 'error',
    ];
    $this->render($messages);
    $this->assertRaw('messages messages--error');
  }

}
