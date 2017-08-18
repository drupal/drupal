<?php

namespace Drupal\workflows;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining workflow entities.
 *
 * @internal
 *   The workflow system is currently experimental and should only be leveraged
 *   by experimental modules and development releases of contributed modules.
 */
interface WorkflowInterface extends ConfigEntityInterface {

  /**
   * Gets the workflow type plugin.
   *
   * @return \Drupal\workflows\WorkflowTypeInterface
   *   The workflow type plugin.
   */
  public function getTypePlugin();

}
