<?php

/**
 * @file
 * Contains \Drupal\shortcut\Plugin\Menu\AddShortcutSetLocalAction.
 */

namespace Drupal\shortcut\Plugin\Menu\LocalAction;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Annotation\Menu\LocalAction;
use Drupal\Core\Menu\LocalActionBase;

/**
 * @LocalAction(
 *   id = "shortcut_set_add_local_action",
 *   route_name = "shortcut_set_add",
 *   title = @Translation("Add shortcut set"),
 *   appears_on = {"shortcut_set_admin"}
 * )
 */
class AddShortcutSetLocalAction extends LocalActionBase {

}
