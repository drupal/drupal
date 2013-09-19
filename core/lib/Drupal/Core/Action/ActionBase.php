<?php

/**
 * @file
 * Contains \Drupal\Core\Action\ActionBase.
 */

namespace Drupal\Core\Action;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Action\ActionInterface;

/**
 * Provides a base implementation for an Action plugin.
 */
abstract class ActionBase extends PluginBase implements ActionInterface {

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    foreach ($entities as $entity) {
      $this->execute($entity);
    }
  }

}
