<?php

/**
 * @file
 * Contains \Drupal\menu_test\Plugin\Menu\LocalTask\TestTasksSettingsSub1.
 */

namespace Drupal\menu_test\Plugin\Menu\LocalTask;

use Drupal\Core\Annotation\Menu\LocalTask;
use Drupal\Core\Menu\LocalTaskBase;
use Drupal\Core\Annotation\Translation;

/**
 * @LocalTask(
 *   id = "menu_local_task_test_tasks_settings_sub1",
 *   route_name = "menu_local_task_test_tasks_settings_sub1",
 *   title = @Translation("sub1"),
 *   tab_root_id = "menu_local_task_test_tasks_view",
 *   tab_parent_id = "menu_local_task_test_tasks_settings",
 *   weight = "-10"
 * )
 */
class TestTasksSettingsSub1 extends LocalTaskBase {

}
