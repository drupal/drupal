<?php

/**
 * @file
 * Definition of Drupal\search\Plugin\views\filter\Search.
 */

namespace Drupal\search\Plugin\views\filter;

use Drupal\search\SearchQuery;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Component\Annotation\PluginID;

/**
 * Field handler to provide simple renderer that allows linking to a node.
 *
 * @ingroup views_filter_handlers
 *
 * @PluginID("search")
 */
class Search extends FilterPluginBase {

  var $always_multiple = TRUE;

  /**
   * Stores an extended query extender from the search module.
   *
   * This value extends the query extender to be able to provide methods
   * which returns the protected values.
   *
   * @var Drupal\search\ViewsSearchQuery
   */
  var $search_query = NULL;

  /**
   * Checks if the search query has been parsed.
   */
  var $parsed = FALSE;

  protected function defineOptions() {
    $options = parent::defineOptions();

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
  protected function valueForm(&$form, &$form_state) {
    $form['value'] = array(
      '#type' => 'textfield',
      '#size' => 15,
      '#default_value' => $this->value,
      '#attributes' => array('title' => t('Enter the terms you wish to search for.')),
      '#title' => empty($form_state['exposed']) ? t('Value') : '',
    );
  }

  /**
   * Validate the options form.
   */
  public function validateExposed(&$form, &$form_state) {
    if (!isset($this->options['expose']['identifier'])) {
      return;
    }

    $key = $this->options['expose']['identifier'];
    if (!empty($form_state['values'][$key])) {
      $this->query_parse_search_expression($form_state['values'][$key]);
      if (count($this->search_query->words()) == 0) {
        form_set_error($key, format_plural(config('search.settings')->get('index.minimum_word_size'), 'You must include at least one positive keyword with 1 character or more.', 'You must include at least one positive keyword with @count characters or more.'));
      }
    }
  }

  /**
   * Take sure that parseSearchExpression is runned and everything is set up for it.
   *
   * @param $input
   *    The search phrase which was input by the user.
   */
  function query_parse_search_expression($input) {
    if (!isset($this->search_query)) {
      $this->parsed = TRUE;
      $this->search_query = db_select('search_index', 'i', array('target' => 'slave'))->extend('Drupal\search\ViewsSearchQuery');
      $this->search_query->searchExpression($input, $this->view->base_table);
      $this->search_query->publicParseSearchExpression();
    }
  }

  /**
   * Add this filter to the query.
   *
   * Due to the nature of fapi, the value and the operator have an unintended
   * level of indirection. You will find them in $this->operator
   * and $this->value respectively.
   */
  public function query() {
    // Since attachment views don't validate the exposed input, parse the search
    // expression if required.
    if (!$this->parsed) {
      $this->query_parse_search_expression($this->value);
    }
    $required = FALSE;
    if (!isset($this->search_query)) {
      $required = TRUE;
    }
    else {
      $words = $this->search_query->words();
      if (empty($words)) {
        $required = TRUE;
      }
    }
    if ($required) {
      if ($this->operator == 'required') {
        $this->query->add_where($this->options['group'], 'FALSE');
      }
    }
    else {
      $search_index = $this->ensureMyTable();

      $search_condition = db_and();

      // Create a new join to relate the 'serach_total' table to our current 'search_index' table.
      $definition = array(
        'table' => 'search_total',
        'field' => 'word',
        'left_table' => $search_index,
        'left_field' => 'word',
      );
      $join = drupal_container()->get('plugin.manager.views.join')->createInstance('standard', $definition);

      $search_total = $this->query->add_relationship('search_total', $join, $search_index);

      $this->search_score = $this->query->add_field('', "SUM($search_index.score * $search_total.count)", 'score', array('aggregate' => TRUE));

      if (empty($this->query->relationships[$this->relationship])) {
        $base_table = $this->view->storage->get('base_table');
      }
      else {
        $base_table = $this->query->relationships[$this->relationship]['base'];
      }
      $search_condition->condition("$search_index.type", $base_table);
      if (!$this->search_query->simple()) {
        $search_dataset = $this->query->addTable('search_dataset');
        $conditions = $this->search_query->conditions();
        $condition_conditions =& $conditions->conditions();
        foreach ($condition_conditions  as $key => &$condition) {
          // Take sure we just look at real conditions.
          if (is_numeric($key)) {
            // Replace the conditions with the table alias of views.
            $this->search_query->condition_replace_string('d.', "$search_dataset.", $condition);
          }
        }
        $search_conditions =& $search_condition->conditions();
        $search_conditions = array_merge($search_conditions, $condition_conditions);
      }
      else {
        // Stores each condition, so and/or on the filter level will still work.
        $or = db_or();
        foreach ($words as $word) {
          $or->condition("$search_index.word", $word);
        }

        $search_condition->condition($or);
      }

      $this->query->add_where($this->options['group'], $search_condition);
      $this->query->add_groupby("$search_index.sid");
      $matches = $this->search_query->matches();
      $placeholder = $this->placeholder();
      $this->query->addHavingExpression($this->options['group'], "COUNT(*) >= $placeholder", array($placeholder => $matches));
    }
    // Set to NULL to prevent PDO exception when views object is cached.
    $this->search_query = NULL;
  }

}
