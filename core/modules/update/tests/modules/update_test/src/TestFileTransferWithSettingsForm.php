<?php

declare(strict_types=1);

namespace Drupal\update_test;

use Drupal\Core\FileTransfer\Local;

/**
 * Provides an object to test the settings form functionality.
 *
 * This class extends \Drupal\Core\FileTransfer\Local to make module install
 * testing via \Drupal\Core\FileTransfer\Form\FileTransferAuthorizeForm and
 * authorize.php possible.
 *
 * @see \Drupal\update\Tests\FileTransferAuthorizeFormTest
 */
class TestFileTransferWithSettingsForm extends Local {

  /**
   * Returns a Drupal\update_test\TestFileTransferWithSettingsForm object.
   *
   * @return static
   *   A new Drupal\update_test\TestFileTransferWithSettingsForm object.
   */
  public static function factory($jail, $settings) {
    return new static($jail, \Drupal::service('file_system'));
  }

  /**
   * Returns a settings form with a text field to input a username.
   */
  public function getSettingsForm() {
    $form = [];
    $form['update_test_username'] = [
      '#type' => 'textfield',
      '#title' => t('Update Test Username'),
    ];
    return $form;
  }

}
