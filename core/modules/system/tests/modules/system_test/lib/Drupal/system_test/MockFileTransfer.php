<?php

/**
 * @file
 * Definition of Drupal\system_test\MockFileTransfer.
 */

namespace Drupal\system_test;

/**
 * Mock FileTransfer object to test the settings form functionality.
 */
class MockFileTransfer {

  /**
   * Returns a Drupal\system_test\MockFileTransfer object.
   *
   * @return \Drupal\system_test\MockFileTransfer
   *   A new Drupal\system_test\MockFileTransfer object.
   */
  public static function factory() {
    return new MockFileTransfer;
  }

  /**
   * Returns a settings form with a text field to input a username.
   */
  public function getSettingsForm() {
    $form = array();
    $form['system_test_username'] = array(
      '#type' => 'textfield',
      '#title' => t('System Test Username'),
    );
    return $form;
  }
}
