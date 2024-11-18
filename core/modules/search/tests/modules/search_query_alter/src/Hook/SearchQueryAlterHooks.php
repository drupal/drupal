<?php

declare(strict_types=1);

namespace Drupal\search_query_alter\Hook;

use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for search_query_alter.
 */
class SearchQueryAlterHooks {

  /**
   * Implements hook_query_TAG_alter().
   *
   * Tags search_$type with $type node_search.
   */
  #[Hook('query_search_node_search_alter')]
  public function querySearchNodeSearchAlter(AlterableInterface $query): void {
    // For testing purposes, restrict the query to node type 'article' only.
    $query->condition('n.type', 'article');
  }

}
