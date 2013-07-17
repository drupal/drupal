<?php

/**
 * @file
 * Contains \Drupal\views_ui\Plugin\Menu\LocalTask\ViewsSettingsTask.
 */

namespace Drupal\views_ui\Plugin\Menu\LocalTask;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Menu\LocalTaskBase;
use Drupal\Core\Annotation\Menu\LocalTask;

/**
 * @LocalTask(
 *   id = "views_ui_settings_tab",
 *   route_name = "views_ui.settings.basic",
 *   title = @Translation("Settings"),
 *   tab_root_id = "views_ui_list_tab"
 * )
 */
class ViewsSettingsTask extends LocalTaskBase {

}
