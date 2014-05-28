<?php
/**
 * @file
 * Contains \Drupal\user\Form\UserForm.
 */

namespace Drupal\user\Form;

/**
 * Temporary form controller for user module.
 */
class UserForm {

  /**
   * Wraps user_pass_reset().
   *
   * @todo Remove user_pass_reset().
   */
  public function resetPass($uid, $timestamp, $hash, $operation) {
    module_load_include('pages.inc', 'user');
    return \Drupal::formBuilder()->getForm('user_pass_reset', $uid, $timestamp, $hash, $operation);
  }

}
