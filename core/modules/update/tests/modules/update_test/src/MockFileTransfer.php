<?php

namespace Drupal\update_test;

/**
 * Mocks a FileTransfer object to test the settings form functionality.
 */
class MockFileTransfer {

  /**
   * Returns a Drupal\update_test\MockFileTransfer object.
   *
   * @return \Drupal\update_test\MockFileTransfer
   *   A new Drupal\update_test\MockFileTransfer object.
   */
  public static function factory() {
    return new FileTransfer();
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
