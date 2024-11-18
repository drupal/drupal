<?php

declare(strict_types=1);

namespace Drupal\search_date_query_alter\Hook;

use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for search_date_query_alter.
 */
class SearchDateQueryAlterHooks {

  /**
   * Implements hook_query_TAG_alter().
   *
   * Tags search_$type with $type node_search.
   */
  #[Hook('query_search_node_search_alter')]
  public function querySearchNodeSearchAlter(AlterableInterface $query): void {
    // Start date Sat, 19 Mar 2016 00:00:00 GMT.
    $query->condition('n.created', 1458345600, '>=');
    // End date Sun, 20 Mar 2016 00:00:00 GMT.
    $query->condition('n.created', 1458432000, '<');
  }

}
