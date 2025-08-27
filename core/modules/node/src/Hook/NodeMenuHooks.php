<?php

declare(strict_types=1);

namespace Drupal\node\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Menu hook implementations for node.
 */
class NodeMenuHooks {

  /**
   * Implements hook_local_tasks_alter().
   */
  #[Hook('local_tasks_alter')]
  public function localTasksAlter(&$local_tasks): void {
    // Removes 'Revisions' local task added by deriver. Local task
    // 'entity.node.version_history' will be replaced by
    // 'entity.version_history:node.version_history' after
    // https://www.drupal.org/project/drupal/issues/3153559.
    unset($local_tasks['entity.version_history:node.version_history']);
  }

}
