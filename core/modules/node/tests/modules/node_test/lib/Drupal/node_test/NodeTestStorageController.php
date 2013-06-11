<?php

/**
 * @file
 * Contains \Drupal\node_test\NodeTestStorageController.
 */

namespace Drupal\node_test;

use Drupal\node\NodeStorageController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a test storage controller for nodes.
 */
class NodeTestStorageController extends NodeStorageController {

  /**
   * {@inheritdoc}
   */
  protected function preSave(EntityInterface $node) {
    // Allow test nodes to specify their updated ('changed') time.
  }

}
