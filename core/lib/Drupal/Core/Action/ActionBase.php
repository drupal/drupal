<?php

namespace Drupal\Core\Action;

use Drupal\Core\Plugin\PluginBase;

/**
 * Provides a base implementation for an Action plugin.
 *
 * @see \Drupal\Core\Annotation\Action
 * @see \Drupal\Core\Action\ActionManager
 * @see \Drupal\Core\Action\ActionInterface
 * @see plugin_api
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
