<?php

declare(strict_types=1);

namespace Drupal\node_test_exception\Hook;

use Drupal\node\NodeInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for node_test_exception.
 */
class NodeTestExceptionHooks {

  /**
   * Implements hook_ENTITY_TYPE_insert() for node entities.
   */
  #[Hook('node_insert')]
  public function nodeInsert(NodeInterface $node) {
    if ($node->getTitle() == 'testing_transaction_exception') {
      throw new \Exception('Test exception for rollback.');
    }
  }

}
