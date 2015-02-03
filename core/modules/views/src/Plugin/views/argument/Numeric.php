<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\argument\Numeric.
 */

namespace Drupal\views\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;

/**
 * Basic argument handler for arguments that are numeric. Incorporates
 * break_phrase.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("numeric")
 */
class Numeric extends ArgumentPluginBase {

  /**
   * The operator used for the query: or|and.
   * @var string
   */
  var $operator;

  /**
   * The actual value which is used for querying.
   * @var array
   */
  var $value;

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['break_phrase'] = array('default' => FALSE);
    $options['not'] = array('default' => FALSE);

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // allow + for or, , for and
    $form['break_phrase'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow multiple values'),
      '#description' => $this->t('If selected, users can enter multiple values in the form of 1+2+3 (for OR) or 1,2,3 (for AND).'),
      '#default_value' => !empty($this->options['break_phrase']),
      '#fieldset' => 'more',
    );

    $form['not'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude'),
      '#description' => $this->t('If selected, the numbers entered for the filter will be excluded rather than limiting the view.'),
      '#default_value' => !empty($this->options['not']),
      '#fieldset' => 'more',
    );
  }

  function title() {
    if (!$this->argument) {
      return !empty($this->definition['empty field name']) ? $this->definition['empty field name'] : $this->t('Uncategorized');
    }

    if (!empty($this->options['break_phrase'])) {
      $break = static::breakString($this->argument, TRUE);
      $this->value = $break->value;
      $this->operator = $break->operator;
    }
    else {
      $this->value = array($this->argument);
      $this->operator = 'or';
    }

    if (empty($this->value)) {
      return !empty($this->definition['empty field name']) ? $this->definition['empty field name'] : $this->t('Uncategorized');
    }

    if ($this->value === array(-1)) {
      return !empty($this->definition['invalid input']) ? $this->definition['invalid input'] : $this->t('Invalid input');
    }

    return implode($this->operator == 'or' ? ' + ' : ', ', $this->titleQuery());
  }

  /**
   * Override for specific title lookups.
   * @return array
   *    Returns all titles, if it's just one title it's an array with one entry.
   */
  public function titleQuery() {
    return $this->value;
  }

  public function query($group_by = FALSE) {
    $this->ensureMyTable();

    if (!empty($this->options['break_phrase'])) {
      $break = static::breakString($this->argument, TRUE);
      $this->value = $break->value;
      $this->operator = $break->operator;
    }
    else {
      $this->value = array($this->argument);
    }

    $placeholder = $this->placeholder();
    $null_check = empty($this->options['not']) ? '' : "OR $this->tableAlias.$this->realField IS NULL";

    if (count($this->value) > 1) {
      $operator = empty($this->options['not']) ? 'IN' : 'NOT IN';
      $placeholder .= '[]';
      $this->query->addWhereExpression(0, "$this->tableAlias.$this->realField $operator($placeholder) $null_check", array($placeholder => $this->value));
    }
    else {
      $operator = empty($this->options['not']) ? '=' : '!=';
      $this->query->addWhereExpression(0, "$this->tableAlias.$this->realField $operator $placeholder $null_check", array($placeholder => $this->argument));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSortName() {
    return $this->t('Numerical', array(), array('context' => 'Sort order'));
  }

}
