<?php
// $Id: views_handler_filter_term_node_tid_depth.inc,v 1.1.6.4 2010/09/09 21:31:49 dereine Exp $
/**
 * Filter handler for taxonomy terms with depth.
 *
 * This handler is actually part of the node table and has some restrictions,
 * because it uses a subquery to find nodes with
 */
class views_handler_filter_term_node_tid_depth extends views_handler_filter_term_node_tid {
  function operator_options() {
    return array(
      'or' => t('Is one of'),
    );
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['depth'] = array('default' => 0);

    return $options;
  }

  function extra_options_form(&$form, &$form_state) {
    parent::extra_options_form($form, $form_state);

    $form['depth'] = array(
      '#type' => 'weight',
      '#title' => t('Depth'),
      '#default_value' => $this->options['depth'],
      '#description' => t('The depth will match nodes tagged with terms in the hierarchy. For example, if you have the term "fruit" and a child term "apple", with a depth of 1 (or higher) then filtering for the term "fruit" will get nodes that are tagged with "apple" as well as "fruit". If negative, the reverse is true; searching for "apple" will also pick up nodes tagged with "fruit" if depth is -1 (or lower).'),
    );
  }

  function query() {
    // If no filter values are present, then do nothing.
    if (count($this->value) == 0) {
      return;
    }
    elseif (count($this->value) == 1) {
      $operator = '=';
    }
    else {
      $operator = 'IN';# " IN (" . implode(', ', array_fill(0, sizeof($this->value), '%d')) . ")";
    }

    // The normal use of ensure_my_table() here breaks Views.
    // So instead we trick the filter into using the alias of the base table.
    // See http://drupal.org/node/271833
    // If a relationship is set, we must use the alias it provides.
    if (!empty($this->relationship)) {
      $this->table_alias = $this->relationship;
    }
    // If no relationship, then use the alias of the base table.
    elseif (isset($this->query->table_queue[$this->query->base_table]['alias'])) {
      $this->table_alias = $this->query->table_queue[$this->query->base_table]['alias'];
    }
    // This should never happen, but if it does, we fail quietly.
    else {
      return;
    }

    // Now build the subqueries.
    $subquery = db_select('taxonomy_index', 'tn');
    $subquery->addField('tn', 'nid');
    $where = db_or()->condition('tn.tid', $this->value, $operator);
    $last = "tn";

    if ($this->options['depth'] > 0) {
      $subquery->leftJoin('taxonomy_term_hierarchy', 'th', "th.tid = tn.tid");
      $last = "th";
      foreach (range(1, abs($this->options['depth'])) as $count) {
        $subquery->leftJoin('taxonomy_term_hierarchy', "th$count", "$last.parent = th$count.tid");
        $where->condition("th$count.tid", $this->value, $operator);
        $last = "th$count";
      }
    }
    elseif ($this->options['depth'] < 0) {
      foreach (range(1, abs($this->options['depth'])) as $count) {
        $subquery->leftJoin('taxonomy_term_hierarchy', "th$count", "$last.tid = th$count.parent");
        $where->condition("th$count.tid", $this->value, $operator);
        $last = "th$count";
      }
    }

    $subquery->condition($where);
    $this->query->add_where(0, "$this->table_alias.$this->real_field", $subquery, 'IN');
  }
}
