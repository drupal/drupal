<?php

/**
 * @file
 * Contains \Drupal\shortcut\Form\ShortcutForm.
 */

namespace Drupal\shortcut\Form;

use Drupal\user\UserInterface;

/**
 * Temporary form controller for shortcut module.
 */
class ShortcutForm {

  /**
   * Wraps shortcut_set_switch().
   *
   * @todo Remove shortcut_set_switch().
   */
  public function overview(UserInterface $user) {
    module_load_include('admin.inc', 'shortcut');
    return drupal_get_form('shortcut_set_switch', $user);
  }

}
