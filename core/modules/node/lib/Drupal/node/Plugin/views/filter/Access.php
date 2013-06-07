<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\filter\Access.
 */

namespace Drupal\node\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Component\Annotation\PluginID;

/**
 * Filter by node_access records.
 *
 * @ingroup views_filter_handlers
 *
 * @PluginID("node_access")
 */
class Access extends FilterPluginBase {

  public function adminSummary() { }
  function operator_form(&$form, &$form_state) { }
  public function canExpose() {
    return FALSE;
  }

  /**
   * See _node_access_where_sql() for a non-views query based implementation.
   */
  public function query() {
    if (!user_access('administer nodes')) {
      $table = $this->ensureMyTable();
      $grants = db_or();
      foreach (node_access_grants('view') as $realm => $gids) {
        foreach ($gids as $gid) {
          $grants->condition(db_and()
            ->condition($table . '.gid', $gid)
            ->condition($table . '.realm', $realm)
          );
        }
      }

      $this->query->addWhere('AND', $grants);
      $this->query->addWhere('AND', $table . '.grant_view', 1, '>=');
    }
  }

}
