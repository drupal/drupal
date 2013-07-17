<?php

/**
 * @file
 * Contains \Drupal\views_ui\Plugin\Menu\LocalTask\ViewsSettingsBasicTask.
 */

namespace Drupal\views_ui\Plugin\Menu\LocalTask;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Menu\LocalTaskBase;
use Drupal\Core\Annotation\Menu\LocalTask;

/**
 * @LocalTask(
 *   id = "views_ui_settings_basic_tab",
 *   route_name = "views_ui.settings.basic",
 *   title = @Translation("Basic"),
 *   tab_root_id = "views_ui_list_tab",
 *   tab_parent_id = "views_ui_settings_tab"
 * )
 */
class ViewsSettingsBasicTask extends LocalTaskBase {

}
