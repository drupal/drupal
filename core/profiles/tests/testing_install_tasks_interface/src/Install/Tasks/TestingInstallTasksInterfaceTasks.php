<?php

declare(strict_types=1);

namespace Drupal\testing_install_tasks_interface\Install\Tasks;

use Drupal\Core\Extension\Install\TasksInterface;

/**
 * Install tasks.
 */
class TestingInstallTasksInterfaceTasks implements TasksInterface {

  /**
   * Implements getTasks.
   */
  public function getTasks(array &$install_state): array {
    \Drupal::keyValue('testing_install_tasks_interface')->set('task', 'ran');
    return [];
  }

  /**
   * Implements alter.
   */
  public function alter(array &$tasks, array $install_state): void {
    \Drupal::keyValue('testing_install_tasks_interface')->set('alter', 'ran');
  }

}
