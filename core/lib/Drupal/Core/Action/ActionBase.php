<?php

/**
 * @file
 * Contains \Drupal\Core\Action\ActionBase.
 */

namespace Drupal\Core\Action;

use Drupal\Core\Action\ActionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginBase;

/**
 * Provides a base implementation for an Action plugin.
 */
abstract class ActionBase extends ContainerFactoryPluginBase implements ActionInterface {

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    foreach ($entities as $entity) {
      $this->execute($entity);
    }
  }

}
