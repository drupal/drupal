<?php

/**
 * @file
 * Definition of Drupal\search\Plugin\views\filter\Search.
 */

namespace Drupal\search\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Filter handler for search keywords.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_keywords")
 */
class Search extends FilterPluginBase {

  /**
   * This filter is always considered multiple-valued.
   *
   * @var bool
   */
  protected $alwaysMultiple = TRUE;

  /**
   * A search query to use for parsing search keywords.
    *
    * @var \Drupal\search\ViewsSearchQuery
    */
  protected $searchQuery = NULL;

  /**
   * TRUE if the search query has been parsed.
   */
  protected $parsed = FALSE;

  /**
   * The search type name (value of {search_index}.type in the database).
   *
   * @var string
   */
  protected $searchType;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->searchType = $this->definition['search_type'];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['operator']['default'] = 'optional';

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function operatorForm(&$form, FormStateInterface $form_state) {
    $form['operator'] = array(
      '#type' => 'radios',
      '#title' => t('On empty input'),
      '#default_value' => $this->operator,
      '#options' => array(
        'optional' => $this->t('Show All'),
        'required' => $this->t('Show None'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = array(
      '#type' => 'textfield',
      '#size' => 15,
      '#default_value' => $this->value,
      '#attributes' => array('title' => $this->t('Search keywords')),
      '#title' => !$form_state->get('exposed') ? $this->t('Keywords') : '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validateExposed(&$form, FormStateInterface $form_state) {
    if (!isset($this->options['expose']['identifier'])) {
      return;
    }

    $key = $this->options['expose']['identifier'];
    if (!$form_state->isValueEmpty($key)) {
      $this->queryParseSearchExpression($form_state->getValue($key));
      if (count($this->searchQuery->words()) == 0) {
        $form_state->setErrorByName($key, format_plural(\Drupal::config('search.settings')->get('index.minimum_word_size'), 'You must include at least one positive keyword with 1 character or more.', 'You must include at least one positive keyword with @count characters or more.'));
      }
    }
  }

  /**
   * Sets up and parses the search query.
   *
   * @param string $input
   *   The search keywords entered by the user.
   */
  protected function queryParseSearchExpression($input) {
    if (!isset($this->searchQuery)) {
      $this->parsed = TRUE;
      $this->searchQuery = db_select('search_index', 'i', array('target' => 'replica'))->extend('Drupal\search\ViewsSearchQuery');
      $this->searchQuery->searchExpression($input, $this->searchType);
      $this->searchQuery->publicParseSearchExpression();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Since attachment views don't validate the exposed input, parse the search
    // expression if required.
    if (!$this->parsed) {
      $this->queryParseSearchExpression($this->value);
    }
    $required = FALSE;
    if (!isset($this->searchQuery)) {
      $required = TRUE;
    }
    else {
      $words = $this->searchQuery->words();
      if (empty($words)) {
        $required = TRUE;
      }
    }
    if ($required) {
      if ($this->operator == 'required') {
        $this->query->addWhere($this->options['group'], 'FALSE');
      }
    }
    else {
      $search_index = $this->ensureMyTable();

      $search_condition = db_and();

      // Create a new join to relate the 'search_total' table to our current
      // 'search_index' table.
      $definition = array(
        'table' => 'search_total',
        'field' => 'word',
        'left_table' => $search_index,
        'left_field' => 'word',
      );
      $join = Views::pluginManager('join')->createInstance('standard', $definition);

      $search_total = $this->query->addRelationship('search_total', $join, $search_index);

      $this->search_score = $this->query->addField('', "SUM($search_index.score * $search_total.count)", 'score', array('aggregate' => TRUE));

      $search_condition->condition("$search_index.type", $this->searchType);
      if (!$this->searchQuery->simple()) {
        $search_dataset = $this->query->addTable('search_dataset');
        $conditions = $this->searchQuery->conditions();
        $condition_conditions =& $conditions->conditions();
        foreach ($condition_conditions  as $key => &$condition) {
          // Make sure we just look at real conditions.
          if (is_numeric($key)) {
            // Replace the conditions with the table alias of views.
            $this->searchQuery->conditionReplaceString('d.', "$search_dataset.", $condition);
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

      $this->query->addWhere($this->options['group'], $search_condition);
      $this->query->addGroupBy("$search_index.sid");
      $matches = $this->searchQuery->matches();
      $placeholder = $this->placeholder();
      $this->query->addHavingExpression($this->options['group'], "COUNT(*) >= $placeholder", array($placeholder => $matches));
    }
    // Set to NULL to prevent PDO exception when views object is cached.
    $this->searchQuery = NULL;
  }

}
