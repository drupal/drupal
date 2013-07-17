<?php

/**
 * @file
 * Contains \Drupal\menu_test\Plugin\Menu\LocalTask\TestTasksEdit.
 */

namespace Drupal\menu_test\Plugin\Menu\LocalTask;

use Drupal\Core\Annotation\Menu\LocalTask;
use Drupal\Core\Menu\LocalTaskBase;
use Drupal\Core\Annotation\Translation;

/**
 * @LocalTask(
 *   id = "menu_local_task_test_tasks_edit",
 *   route_name = "menu_local_task_test_tasks_edit",
 *   title = @Translation("Edit"),
 *   tab_root_id = "menu_local_task_test_tasks_view",
 *   weight = "10"
 * )
 */
class TestTasksEdit extends LocalTaskBase {

}
