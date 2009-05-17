<?php

/**
 * @file
 * Definition of views_handler_argument_term_node_tid_depth.
 */

/**
 * Argument handler for taxonomy terms with depth.
 *
 * This handler is actually part of the node table and has some restrictions,
 * because it uses a subquery to find nodes with.
 *
 * @ingroup views_argument_handlers
 */
class views_handler_argument_term_node_tid_depth extends views_handler_argument {
  function option_definition() {
    $options = parent::option_definition();

    $options['depth'] = array('default' => 0);
    $options['break_phrase'] = array('default' => FALSE, 'bool' => TRUE);
    $options['set_breadcrumb'] = array('default' => FALSE, 'bool' => TRUE);
    $options['use_taxonomy_term_path'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    $form['depth'] = array(
      '#type' => 'weight',
      '#title' => t('Depth'),
      '#default_value' => $this->options['depth'],
      '#description' => t('The depth will match nodes tagged with terms in the hierarchy. For example, if you have the term "fruit" and a child term "apple", with a depth of 1 (or higher) then filtering for the term "fruit" will get nodes that are tagged with "apple" as well as "fruit". If negative, the reverse is true; searching for "apple" will also pick up nodes tagged with "fruit" if depth is -1 (or lower).'),
    );

    $form['break_phrase'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow multiple values'),
      '#description' => t('If selected, users can enter multiple values in the form of 1+2+3. Due to the number of JOINs it would require, AND will be treated as OR with this filter.'),
      '#default_value' => !empty($this->options['break_phrase']),
    );

    $form['set_breadcrumb'] = array(
      '#type' => 'checkbox',
      '#title' => t("Set the breadcrumb for the term parents"),
      '#description' => t('If selected, the breadcrumb trail will include all parent terms, each one linking to this view. Note that this only works if just one term was received.'),
      '#default_value' => !empty($this->options['set_breadcrumb']),
    );

    $form['use_taxonomy_term_path'] = array(
      '#type' => 'checkbox',
      '#title' => t("Use Drupal's taxonomy term path to create breadcrumb links"),
      '#description' => t('If selected, the links in the breadcrumb trail will be created using the standard drupal method instead of the custom views method. This is useful if you are using modules like taxonomy redirect to modify your taxonomy term links.'),
      '#default_value' => !empty($this->options['use_taxonomy_term_path']),
      '#dependency' => array('edit-options-set-breadcrumb' => array(TRUE)),
    );
    parent::options_form($form, $form_state);
  }

  function set_breadcrumb(&$breadcrumb) {
    if (empty($this->options['set_breadcrumb']) || !is_numeric($this->argument)) {
      return;
    }

    return views_taxonomy_set_breadcrumb($breadcrumb, $this);
  }

  /**
   * Override default_actions() to remove summary actions.
   */
  function default_actions($which = NULL) {
    if ($which) {
      if (in_array($which, array('ignore', 'not found', 'empty', 'default'))) {
        return parent::default_actions($which);
      }
      return;
    }
    $actions = parent::default_actions();
    unset($actions['summary asc']);
    unset($actions['summary desc']);
    unset($actions['summary asc by count']);
    unset($actions['summary desc by count']);
    return $actions;
  }

  function query($group_by = FALSE) {
    $this->ensure_my_table();

    if (!empty($this->options['break_phrase'])) {
      $tids = new stdClass();
      $tids->value = $this->argument;
      $tids = views_break_phrase($this->argument, $tids);
      if ($tids->value == array(-1)) {
        return FALSE;
      }

      if (count($tids->value) > 1) {
        $operator = 'IN';
      }
      else {
        $operator = '=';
      }

      $tids = $tids->value;
    }
    else {
      $operator = "=";
      $tids = $this->argument;
    }
    // Now build the subqueries.
    $subquery = db_select('taxonomy_index', 'tn');
    $subquery->addField('tn', 'nid');
    $where = db_or()->condition('tn.tid', $tids, $operator);
    $last = "tn";

    if ($this->options['depth'] > 0) {
      $subquery->leftJoin('taxonomy_term_hierarchy', 'th', "th.tid = tn.tid");
      $last = "th";
      foreach (range(1, abs($this->options['depth'])) as $count) {
        $subquery->leftJoin('taxonomy_term_hierarchy', "th$count", "$last.parent = th$count.tid");
        $where->condition("th$count.tid", $tids, $operator);
        $last = "th$count";
      }
    }
    elseif ($this->options['depth'] < 0) {
      foreach (range(1, abs($this->options['depth'])) as $count) {
        $subquery->leftJoin('taxonomy_term_hierarchy', "th$count", "$last.tid = th$count.parent");
        $where->condition("th$count.tid", $tids, $operator);
        $last = "th$count";
      }
    }

    $subquery->condition($where);
    $this->query->add_where(0, "$this->table_alias.$this->real_field", $subquery, 'IN');
  }

  function title() {
    $term = taxonomy_term_load($this->argument);
    if (!empty($term)) {
      return check_plain($term->name);
    }
    // TODO review text
    return t('No name');
  }
}
