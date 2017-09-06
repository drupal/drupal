<?php

namespace Drupal\workflows;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining workflow entities.
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
