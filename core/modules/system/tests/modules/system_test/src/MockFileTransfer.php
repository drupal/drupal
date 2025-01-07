<?php

declare(strict_types=1);

namespace Drupal\system_test;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Mock FileTransfer object to test the settings form functionality.
 */
class MockFileTransfer {

  use StringTranslationTrait;

  /**
   * Returns a Drupal\system_test\MockFileTransfer object.
   *
   * @return \Drupal\system_test\MockFileTransfer
   *   A new Drupal\system_test\MockFileTransfer object.
   */
  public static function factory() {
    return new MockFileTransfer();
  }

  /**
   * Returns a settings form with a text field to input a username.
   */
  public function getSettingsForm() {
    $form = [];
    $form['system_test_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('System Test Username'),
    ];
    return $form;
  }

}
