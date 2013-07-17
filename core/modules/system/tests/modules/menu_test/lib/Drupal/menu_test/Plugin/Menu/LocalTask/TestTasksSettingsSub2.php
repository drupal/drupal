<?php

/**
 * @file
 * Contains \Drupal\menu_test\Plugin\Menu\LocalTask\TestTasksSettingsSub2.
 */

namespace Drupal\menu_test\Plugin\Menu\LocalTask;

use Drupal\Core\Annotation\Menu\LocalTask;
use Drupal\Core\Menu\LocalTaskBase;
use Drupal\Core\Annotation\Translation;

/**
 * @LocalTask(
 *   id = "menu_local_task_test_tasks_settings_sub2",
 *   route_name = "menu_local_task_test_tasks_settings_sub2",
 *   title = @Translation("sub2"),
 *   tab_root_id = "menu_local_task_test_tasks_view",
 *   tab_parent_id = "menu_local_task_test_tasks_settings"
 * )
 */
class TestTasksSettingsSub2 extends LocalTaskBase {

}
