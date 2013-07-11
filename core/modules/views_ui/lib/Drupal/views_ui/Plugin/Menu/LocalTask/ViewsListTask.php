<?php

/**
 * @file
 * Contains \Drupal\views_ui\Plugin\Menu\LocalTask\ViewsListTask.
 */

namespace Drupal\views_ui\Plugin\Menu\LocalTask;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Menu\LocalTaskBase;
use Drupal\Core\Annotation\Menu\LocalTask;


/**
 * @LocalTask(
 *   id = "views_ui_list_tab",
 *   route_name = "views_ui.list",
 *   title = @Translation("List"),
 *   tab_root_id = "views_ui_list_tab"
 * )
 */
class ViewsListTask extends LocalTaskBase {

}
