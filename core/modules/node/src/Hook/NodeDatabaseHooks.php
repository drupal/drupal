<?php

declare(strict_types=1);

namespace Drupal\node\Hook;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Database hook implementations for node.
 */
class NodeDatabaseHooks {

  /**
   * Implements hook_query_TAG_alter().
   *
   * This is the hook_query_alter() for queries tagged with 'node_access'. It
   * adds node access checks for the user account given by the 'account'
   * meta-data (or current user if not provided), for an operation given by the
   * 'op' meta-data (or 'view' if not provided; other possible values are
   * 'update' and 'delete').
   *
   * Queries tagged with 'node_access' that are not against the {node} table
   * must add the base table as metadata. For example:
   * @code
   *   $query
   *     ->addTag('node_access')
   *     ->addMetaData('base_table', 'taxonomy_index');
   * @endcode
   */
  #[Hook('query_node_access_alter')]
  public function queryNodeAccessAlter(AlterableInterface $query): void {
    // Read meta-data from query, if provided.
    if (!($account = $query->getMetaData('account'))) {
      $account = \Drupal::currentUser();
    }
    if (!($op = $query->getMetaData('op'))) {
      $op = 'view';
    }
    // If $account can bypass node access, or there are no node access modules,
    // or the operation is 'view' and the $account has a global view grant
    // (such as a view grant for node ID 0), we don't need to alter the query.
    if ($account->hasPermission('bypass node access')) {
      return;
    }
    if (!\Drupal::moduleHandler()->hasImplementations('node_grants')) {
      return;
    }
    if ($op == 'view' && \Drupal::entityTypeManager()->getAccessControlHandler('node')->checkAllGrants($account)) {
      return;
    }
    $tables = $query->getTables();
    $base_table = $query->getMetaData('base_table');
    // If the base table is not given, default to one of the node base tables.
    if (!$base_table) {
      /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
      $table_mapping = \Drupal::entityTypeManager()->getStorage('node')->getTableMapping();
      $node_base_tables = $table_mapping->getTableNames();
      foreach ($tables as $table_info) {
        if (!$table_info instanceof SelectInterface) {
          $table = $table_info['table'];
          // Ensure that 'node' and 'node_field_data' are always preferred over
          // 'node_revision' and 'node_field_revision'.
          if ($table == 'node' || $table == 'node_field_data') {
            $base_table = $table;
            break;
          }
          // If one of the node base tables are in the query, add it to the list
          // of possible base tables to join against.
          if (in_array($table, $node_base_tables)) {
            $base_table = $table;
          }
        }
      }
      // Bail out if the base table is missing.
      if (!$base_table) {
        throw new \Exception('Query tagged for node access but there is no node table, specify the base_table using meta data.');
      }
    }
    // Update the query for the given storage method.
    \Drupal::service('node.grant_storage')->alterQuery($query, $tables, $op, $account, $base_table);
    // Bubble the 'user.node_grants:$op' cache context to the current render
    // context.
    $renderer = \Drupal::service('renderer');
    if ($renderer->hasRenderContext()) {
      $build = ['#cache' => ['contexts' => ['user.node_grants:' . $op]]];
      $renderer->render($build);
    }
  }

}
