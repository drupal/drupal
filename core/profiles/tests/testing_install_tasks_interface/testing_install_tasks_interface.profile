<?php

/**
 * @file
 * Implement hooks.
 */

declare(strict_types=1);

/**
 * This implements install_tasks.
 *
 * We do not have implements so this does not get converted.
 * It should not run because this profile's TasksInterface supersedes
 * procedural implementations.
 */
function testing_hooks_install_tasks(): void {
  \Drupal::keyValue('testing_install_tasks_interface')->set('procedural_task', 'ran');
}

/**
 * This implements install_tasks_alter.
 *
 * We do not have implements so this does not get converted.
 * It should not run because this profile's TasksInterface supersedes
 * procedural implementations.
 */
function testing_hooks_install_tasks_alter(): void {
  \Drupal::keyValue('testing_install_tasks_interface')->set('procedural_alter', 'ran');
}
