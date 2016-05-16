<?php

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
   * @return \Drupal\update_test\TestFileTransferWithSettingsForm
   *   A new Drupal\update_test\TestFileTransferWithSettingsForm object.
   */
  public static function factory($jail, $settings) {
    return new static($jail);
  }

  /**
   * Returns a settings form with a text field to input a username.
   */
  public function getSettingsForm() {
    $form = array();
    $form['update_test_username'] = array(
      '#type' => 'textfield',
      '#title' => t('Update Test Username'),
    );
    return $form;
  }

}
