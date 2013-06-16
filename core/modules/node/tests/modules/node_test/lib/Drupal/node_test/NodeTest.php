<?php

/**
 * @file
 * Contains \Drupal\node_test\NodeTest.
 */

namespace Drupal\node_test;

use Drupal\node\Plugin\Core\Entity\Node;
use Drupal\Core\Entity\EntityStorageControllerInterface;

/**
 * Overrides the default node entity class for testing.
 */
class NodeTest extends Node {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    // Allow test nodes to specify their updated ('changed') time.
  }

}
