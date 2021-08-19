<?php

namespace Drupal\taxonomy;

use Drupal\views\Views;

/**
 * Builds a performant depth subquery and adds it as a join to the query.
 *
 * This is performant because:
 * - It creates multiple queries on taxonomy_index with inner joins to
 *   taxonomy_term__parent. These queries are combined together into a subquery
 *   using unions to select all the node IDs with the terms in the hierarchy.
 * - It joins the resulting query to the main views query using an INNER JOIN.
 *
 * For example, if the $tids value is '718' and depth is configured to 2, the
 * resulting JOIN to node_field_data will be:
 * @code
 * INNER JOIN (SELECT tn.nid AS nid
 * FROM
 * taxonomy_index tn
 * WHERE tn.tid = '718' UNION SELECT tn.nid AS nid
 * FROM
 * taxonomy_index tn
 * INNER JOIN taxonomy_term__parent th ON tn.tid = th.entity_id
 * INNER JOIN taxonomy_term__parent th1 ON th.parent_target_id = th1.entity_id
 * WHERE th1.entity_id = '718' UNION SELECT tn.nid AS nid
 * FROM
 * taxonomy_index tn
 * INNER JOIN taxonomy_term__parent th ON tn.tid = th.entity_id
 * INNER JOIN taxonomy_term__parent th1 ON th.parent_target_id = th1.entity_id
 * INNER JOIN taxonomy_term__parent th2 ON th1.parent_target_id = th2.entity_id
 * WHERE th2.entity_id = '718') taxonomy_index_depth ON node_field_data.nid = taxonomy_index_depth.nid
 * @endcode
 */
trait TaxonomyIndexDepthQueryTrait {

  /**
   * Builds a performant depth subquery and adds it as a join to the query.
   *
   * @param string|array $tids
   *   The terms ID(s) to do a depth search for.
   */
  protected function addSubQueryJoin($tids): void {
    $connection = $this->query->getConnection();
    $operator = is_array($tids) ? 'IN' : '=';
    // Create the depth 0 subquery.
    $subquery = $connection->select('taxonomy_index', 'tn');
    $subquery->addField('tn', 'nid');
    $subquery->condition('tn.tid', $tids, $operator);

    if ($this->options['depth'] !== 0) {
      // Set $left_field and $right_field depending on whether we are traversing
      // up or down the hierarchy.
      if ($this->options['depth'] > 0) {
        $left_field = 'parent_target_id';
        $right_field = 'entity_id';
      }
      else {
        $left_field = 'entity_id';
        $right_field = 'parent_target_id';
      }
      // Traverse the hierarchy to check the child or parent terms.
      foreach (range(1, abs($this->options['depth'])) as $count) {
        $union_query = $connection->select('taxonomy_index', 'tn');
        $union_query->addField('tn', 'nid');
        $left_join = "[tn].[tid]";
        if ($this->options['depth'] > 0) {
          $union_query->join('taxonomy_term__parent', "th", "$left_join = [th].[entity_id]");
          $left_join = "[th].[$left_field]";
        }
        foreach (range(1, $count) as $inner_count) {
          $union_query->join('taxonomy_term__parent', "th$inner_count", "$left_join = [th$inner_count].[$right_field]");
          $left_join = "[th$inner_count].[$left_field]";
        }
        $union_query->condition("th$inner_count.entity_id", $tids, $operator);
        $subquery->union($union_query);
      }
    }

    // Add the subquery as a join.
    $definition['left_table'] = $this->tableAlias;
    $definition['left_field'] = $this->realField;
    $definition['field'] = 'nid';
    $definition['type'] = 'INNER';
    $definition['adjusted'] = TRUE;
    $definition['table formula'] = $subquery;
    $join = Views::pluginManager('join')->createInstance('standard', $definition);

    // There is no $base as we are joining to a query.
    $this->query->addRelationship('taxonomy_index_depth', $join, NULL, $this->relationship);
  }

}
