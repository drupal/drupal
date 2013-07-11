<?php

/**
 * @file
 * Contains \Drupal\views_ui\Plugin\Menu\AddViewLocalAction.
 */

namespace Drupal\views_ui\Plugin\Menu\LocalAction;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Menu\LocalActionBase;
use Drupal\Core\Annotation\Menu\LocalAction;

/**
 * @LocalAction(
 *   id = "views_add_local_action",
 *   route_name = "views_ui.add",
 *   title = @Translation("Add new view"),
 *   appears_on = {"views_ui.list"}
 * )
 */
class AddViewLocalAction extends LocalActionBase {

}
