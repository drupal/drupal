<?php

/**
 * @file
 * Contains \Drupal\views_ui\Plugin\Menu\LocalTask\ViewsSettingsAdvancedTas.
 */

namespace Drupal\views_ui\Plugin\Menu\LocalTask;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Menu\LocalTaskBase;
use Drupal\Core\Annotation\Menu\LocalTask;

/**
 * @LocalTask(
 *   id = "views_ui_settings_advanced_tab",
 *   route_name = "views_ui.settings.advanced",
 *   title = @Translation("Advanced"),
 *   tab_root_id = "views_ui_list_tab",
 *   tab_parent_id = "views_ui_settings_tab",
 *   weight = "10"
 * )
 */
class ViewsSettingsAdvancedTask extends LocalTaskBase {

}
