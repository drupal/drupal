<?php

declare(strict_types=1);

namespace Drupal\node_access_test_empty\Hook;

use Drupal\node\NodeInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for node_access_test_empty.
 */
class NodeAccessTestEmptyHooks {

  /**
   * Implements hook_node_grants().
   */
  #[Hook('node_grants')]
  public function nodeGrants($account, $operation) {
    return [];
  }

  /**
   * Implements hook_node_access_records().
   */
  #[Hook('node_access_records')]
  public function nodeAccessRecords(NodeInterface $node) {
    return [];
  }

}
