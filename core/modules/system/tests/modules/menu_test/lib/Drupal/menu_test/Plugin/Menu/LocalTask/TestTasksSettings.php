<?php

/**
 * @file
 * Contains \Drupal\menu_test\Plugin\Menu\LocalTask\TestTasksSettings.
 */

namespace Drupal\menu_test\Plugin\Menu\LocalTask;

use Drupal\Core\Annotation\Menu\LocalTask;
use Drupal\Core\Menu\LocalTaskBase;
use Drupal\Core\Annotation\Translation;

/**
 * @LocalTask(
 *   id = "menu_local_task_test_tasks_settings",
 *   route_name = "menu_local_task_test_tasks_settings",
 *   title = @Translation("Settings"),
 *   tab_root_id = "menu_local_task_test_tasks_view"
 * )
 */
class TestTasksSettings extends LocalTaskBase {

}
