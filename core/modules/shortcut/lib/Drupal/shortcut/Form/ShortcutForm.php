<?php
/**
 * @file
 * Contains \Drupal\shortcut\Form\ShortcutForm.
 */

namespace Drupal\shortcut\Form;

use Drupal\menu_link\MenuLinkInterface;
use Drupal\shortcut\ShortcutSetInterface;
use Drupal\user\UserInterface;

/**
 * Temporary form controller for shortcut module.
 */
class ShortcutForm {

  /**
   * Wraps shortcut_link_edit().
   *
   * @todo Remove shortcut_link_edit().
   */
  public function edit(MenuLinkInterface $menu_link) {
    module_load_include('admin.inc', 'shortcut');
    return drupal_get_form('shortcut_link_edit', $menu_link);
  }

  /**
   * Wraps shortcut_link_add().
   *
   * @todo Remove shortcut_link_add().
   */
  public function add(ShortcutSetInterface $shortcut_set) {
    module_load_include('admin.inc', 'shortcut');
    return drupal_get_form('shortcut_link_add', $shortcut_set);
  }

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
