<?php
// $Id: views_handler_filter_search.inc,v 1.5.6.2 2009/12/24 00:37:00 merlinofchaos Exp $

/**
 * Field handler to provide simple renderer that allows linking to a node.
 */
class views_handler_filter_search extends views_handler_filter {
  var $no_single = TRUE;
  function option_definition() {
    $options = parent::option_definition();

    $options['operator']['default'] = 'optional';

    return $options;
  }

  /**
   * Provide simple equality operator
   */
  function operator_form(&$form, &$form_state) {
    $form['operator'] = array(
      '#type' => 'radios',
      '#title' => t('On empty input'),
      '#default_value' => $this->operator,
      '#options' => array(
        'optional' => t('Show All'),
        'required' => t('Show None'),
      ),
    );
  }

  /**
   * Provide a simple textfield for equality
   */
  function exposed_form(&$form, &$form_state) {
    if (isset($this->options['expose']['identifier'])) {
      $key = $this->options['expose']['identifier'];
      $form[$key] = array(
        '#type' => 'textfield',
        '#size' => 15,
        '#default_value' => $this->value,
        '#attributes' => array('title' => t('Enter the terms you wish to search for.')),
      );
    }
  }

  /**
   * Validate the options form.
   */
  function exposed_validate($form, &$form_state) {
    if (!isset($this->options['expose']['identifier'])) {
      return;
    }

    $key = $this->options['expose']['identifier'];
    if (!empty($form_state['values'][$key])) {
      $this->search_query = search_parse_query($form_state['values'][$key]);

      if ($this->search_query[2] == '') {
        form_set_error($key, t('You must include at least one positive keyword with @count characters or more.', array('@count' => variable_get('minimum_word_size', 3))));
      }
      if ($this->search_query[6]) {
        if ($this->search_query[6] == 'or') {
          drupal_set_message(t('Search for either of the two terms with uppercase <strong>OR</strong>. For example, <strong>cats OR dogs</strong>.'));
        }
      }
    }
  }

  /**
   * Add this filter to the query.
   *
   * Due to the nature of fapi, the value and the operator have an unintended
   * level of indirection. You will find them in $this->operator
   * and $this->value respectively.
   */
  function query() {
    if (!isset($this->search_query) || empty($this->search_query[3])) {
      if ($this->operator == 'required') {
        $this->query->add_where($this->options['group'], 'FALSE');
      }
    }
    else {
      $search_index = $this->ensure_my_table();
      $this->search_query[2] = str_replace('i.', "$search_index.", $this->search_query[2]);

      // Create a new join to relate the 'serach_total' table to our current 'search_index' table.
      $join = new views_join;
      $join->construct('search_total', $search_index, 'word', 'word');
      $search_total = $this->query->add_relationship('search_total', $join, $search_index);

      $this->search_score = $this->query->add_field('', "SUM($search_index.score * $search_total.count)", 'score', array('aggregate' => TRUE));

      $this->query->add_where($this->options['group'], $this->search_query[2], $this->search_query[3]);

      if (empty($this->query->relationships[$this->relationship])) {
        $base_table = $this->query->base_table;
      }
      else {
        $base_table = $this->query->relationships[$this->relationship]['base'];
      }
      $this->query->add_where($this->options['group'], "$search_index.type = '%s'", $base_table);
      if (!$this->search_query[5]) {
        $search_dataset = $this->query->add_table('search_dataset');
        $this->search_query[0] = str_replace('d.', "$search_dataset.", $this->search_query[0]);
        $this->query->add_where($this->options['group'], $this->search_query[0], $this->search_query[1]);
      }

      $this->query->add_groupby("$search_index.sid");
      $this->query->add_having($this->options['group'], 'COUNT(*)', $this->search_query[4], '>=');
    }
  }
}
