<?php

/**
 * @file
 * Contains \Drupal\views_test_data\Plugin\views\query\QueryTest.
 */

namespace Drupal\views_test_data\Plugin\views\query;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\Plugin\views\join\JoinPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Defines a query test plugin.
 *
 * @ViewsQuery(
 *   id = "query_test",
 *   title = @Translation("Query test"),
 *   help = @Translation("Defines a query test plugin.")
 * )
 */
class QueryTest extends QueryPluginBase {
  protected $conditions = array();
  protected $fields = array();
  protected $allItems = array();
  protected $orderBy = array();

  /**
   * Implements \Drupal\views\Plugin\views\query\QueryPluginBase::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['test_setting'] = array('default' => '');

    return $options;
  }

  /**
   * Implements \Drupal\views\Plugin\views\query\QueryPluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['test_setting'] = array(
      '#title' => $this->t('Test setting'),
      '#type' => 'textfield',
      '#default_value' => $this->options['test_setting'],
    );
  }

  /**
   * Sets the allItems property.
   *
   * @param array $allItems
   *   An array of stdClasses.
   */
  public function setAllItems($allItems) {
    $this->allItems = $allItems;
  }

  public function addWhere($group, $field, $value = NULL, $operator = NULL) {
    $this->conditions[] = array(
      'field' => $field,
      'value' => $value,
      'operator' => $operator
    );

  }

  public function addField($table, $field, $alias = '', $params = array()) {
    $this->fields[$field] = $field;
    return $field;
  }

  public function addOrderBy($table, $field = NULL, $order = 'ASC', $alias = '', $params = array()) {
    $this->orderBy = array('field' => $field, 'order' => $order);
  }


  public function ensureTable($table, $relationship = NULL, JoinPluginBase $join = NULL) {
    // There is no concept of joins.
  }

  /**
   * Implements Drupal\views\Plugin\views\query\QueryPluginBase::build().
   *
   * @param \Drupal\views\ViewExecutable $view
   */
  public function build(ViewExecutable $view) {
    $this->view = $view;
    // @todo Support pagers for know, a php based one would probably match.
    // @todo You could add a string representation of the query.
    $this->view->build_info['query'] = "";
    $this->view->build_info['count_query'] = "";
}

  /**
   * Implements Drupal\views\Plugin\views\query\QueryPluginBase::execute().
   */
  public function execute(ViewExecutable $view) {
    $result = array();
    foreach ($this->allItems as $element) {
      // Run all conditions on the element, and add it to the result if they
      // match.
      $match = TRUE;
      foreach ($this->conditions as $condition) {
        $match &= $this->match($element, $condition);
      }
      if ($match) {
        // If the query explicit defines fields to use, filter all others out.
        // Filter out fields
        if ($this->fields) {
          $element = array_intersect_key($element, $this->fields);
        }
        $result[] = new ResultRow($element);
      }
    }
    $this->view->result = $result;
  }

  /**
   * Check a single condition for a single element.
   *
   * @param array $element
   *   The element which should be checked.
   * @param array $condition
   *   An associative array containing:
   *   - field: The field to by, for example id.
   *   - value: The expected value of the element.
   *   - operator: The operator to compare the element value with the expected
   *     value.
   *
   * @return bool
   *   Returns whether the condition matches with the element.
   */
  public function match($element, $condition) {
    $value = $element[$condition['field']];
    switch ($condition['operator']) {
      case '=':
        return $value == $condition['value'];
      case 'IN':
        return in_array($value, $condition['value']);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return parent::calculateDependencies() + [
      'content' => ['QueryTest'],
    ];
  }

}
