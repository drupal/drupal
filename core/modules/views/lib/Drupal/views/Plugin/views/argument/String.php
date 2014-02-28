<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\argument\String.
 */

namespace Drupal\views\Plugin\views\argument;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ManyToOneHelper;

/**
 * Basic argument handler to implement string arguments that may have length
 * limits.
 *
 * @ingroup views_argument_handlers
 *
 * @PluginID("string")
 */
class String extends ArgumentPluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\argument\ArgumentPluginBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    if (!empty($this->definition['many to one'])) {
      $this->helper = new ManyToOneHelper($this);

      // Ensure defaults for these, during summaries and stuff:
      $this->operator = 'or';
      $this->value = array();
    }
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['glossary'] = array('default' => FALSE, 'bool' => TRUE);
    $options['limit'] = array('default' => 0);
    $options['case'] = array('default' => 'none');
    $options['path_case'] = array('default' => 'none');
    $options['transform_dash'] = array('default' => FALSE, 'bool' => TRUE);
    $options['break_phrase'] = array('default' => FALSE, 'bool' => TRUE);

    if (!empty($this->definition['many to one'])) {
      $options['add_table'] = array('default' => FALSE, 'bool' => TRUE);
      $options['require_value'] = array('default' => FALSE, 'bool' => TRUE);
    }

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['glossary'] = array(
      '#type' => 'checkbox',
      '#title' => t('Glossary mode'),
      '#description' => t('Glossary mode applies a limit to the number of characters used in the filter value, which allows the summary view to act as a glossary.'),
      '#default_value' => $this->options['glossary'],
      '#fieldset' => 'more',
    );

    $form['limit'] = array(
      '#type' => 'textfield',
      '#title' => t('Character limit'),
      '#description' => t('How many characters of the filter value to filter against. If set to 1, all fields starting with the first letter in the filter value would be matched.'),
      '#default_value' => $this->options['limit'],
      '#states' => array(
        'visible' => array(
          ':input[name="options[glossary]"]' => array('checked' => TRUE),
        ),
      ),
      '#fieldset' => 'more',
    );

    $form['case'] = array(
      '#type' => 'select',
      '#title' => t('Case'),
      '#description' => t('When printing the title and summary, how to transform the case of the filter value.'),
      '#options' => array(
        'none' => t('No transform'),
        'upper' => t('Upper case'),
        'lower' => t('Lower case'),
        'ucfirst' => t('Capitalize first letter'),
        'ucwords' => t('Capitalize each word'),
      ),
      '#default_value' => $this->options['case'],
      '#fieldset' => 'more',
    );

    $form['path_case'] = array(
      '#type' => 'select',
      '#title' => t('Case in path'),
      '#description' => t('When printing url paths, how to transform the case of the filter value. Do not use this unless with Postgres as it uses case sensitive comparisons.'),
      '#options' => array(
        'none' => t('No transform'),
        'upper' => t('Upper case'),
        'lower' => t('Lower case'),
        'ucfirst' => t('Capitalize first letter'),
        'ucwords' => t('Capitalize each word'),
      ),
      '#default_value' => $this->options['path_case'],
      '#fieldset' => 'more',
    );

    $form['transform_dash'] = array(
      '#type' => 'checkbox',
      '#title' => t('Transform spaces to dashes in URL'),
      '#default_value' => $this->options['transform_dash'],
      '#fieldset' => 'more',
    );

    if (!empty($this->definition['many to one'])) {
      $form['add_table'] = array(
        '#type' => 'checkbox',
        '#title' => t('Allow multiple filter values to work together'),
        '#description' => t('If selected, multiple instances of this filter can work together, as though multiple values were supplied to the same filter. This setting is not compatible with the "Reduce duplicates" setting.'),
        '#default_value' => !empty($this->options['add_table']),
        '#fieldset' => 'more',
      );

      $form['require_value'] = array(
        '#type' => 'checkbox',
        '#title' => t('Do not display items with no value in summary'),
        '#default_value' => !empty($this->options['require_value']),
        '#fieldset' => 'more',
      );
    }

    // allow + for or, , for and
    $form['break_phrase'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow multiple values'),
      '#description' => t('If selected, users can enter multiple values in the form of 1+2+3 (for OR) or 1,2,3 (for AND).'),
      '#default_value' => !empty($this->options['break_phrase']),
      '#fieldset' => 'more',
    );
  }

  /**
   * Build the summary query based on a string
   */
  protected function summaryQuery() {
    if (empty($this->definition['many to one'])) {
      $this->ensureMyTable();
    }
    else {
      $this->tableAlias = $this->helper->summaryJoin();
    }

    if (empty($this->options['glossary'])) {
      // Add the field.
      $this->base_alias = $this->query->addField($this->tableAlias, $this->realField);
      $this->query->setCountField($this->tableAlias, $this->realField);
    }
    else {
      // Add the field.
      $formula = $this->getFormula();
      $this->base_alias = $this->query->addField(NULL, $formula, $this->field . '_truncated');
      $this->query->setCountField(NULL, $formula, $this->field, $this->field . '_truncated');
    }

    $this->summaryNameField();
    return $this->summaryBasics(FALSE);
  }

  /**
   * Get the formula for this argument.
   *
   * $this->ensureMyTable() MUST have been called prior to this.
   */
  public function getFormula() {
    return "SUBSTRING($this->tableAlias.$this->realField, 1, " . intval($this->options['limit']) . ")";
  }

  /**
   * Build the query based upon the formula
   */
  public function query($group_by = FALSE) {
    $argument = $this->argument;
    if (!empty($this->options['transform_dash'])) {
      $argument = strtr($argument, '-', ' ');
    }

    if (!empty($this->options['break_phrase'])) {
      $this->breakPhraseString($argument, $this);
    }
    else {
      $this->value = array($argument);
      $this->operator = 'or';
    }

    if (!empty($this->definition['many to one'])) {
      if (!empty($this->options['glossary'])) {
        $this->helper->formula = TRUE;
      }
      $this->helper->ensureMyTable();
      $this->helper->addFilter();
      return;
    }

    $this->ensureMyTable();
    $formula = FALSE;
    if (empty($this->options['glossary'])) {
      $field = "$this->tableAlias.$this->realField";
    }
    else {
      $formula = TRUE;
      $field = $this->getFormula();
    }

    if (count($this->value) > 1) {
      $operator = 'IN';
      $argument = $this->value;
    }
    else {
      $operator = '=';
    }

    if ($formula) {
      $placeholder = $this->placeholder();
      if ($operator == 'IN') {
        $field .= " IN($placeholder)";
      }
      else {
        $field .= ' = ' . $placeholder;
      }
      $placeholders = array(
        $placeholder => $argument,
      );
      $this->query->addWhereExpression(0, $field, $placeholders);
    }
    else {
      $this->query->addWhere(0, $field, $argument, $operator);
    }
  }

  public function summaryArgument($data) {
    $value = $this->caseTransform($data->{$this->base_alias}, $this->options['path_case']);
    if (!empty($this->options['transform_dash'])) {
      $value = strtr($value, ' ', '-');
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSortName() {
    return t('Alphabetical', array(), array('context' => 'Sort order'));
  }

  function title() {
    $this->argument = $this->caseTransform($this->argument, $this->options['case']);
    if (!empty($this->options['transform_dash'])) {
      $this->argument = strtr($this->argument, '-', ' ');
    }

    if (!empty($this->options['break_phrase'])) {
      $this->breakPhraseString($this->argument, $this);
    }
    else {
      $this->value = array($this->argument);
      $this->operator = 'or';
    }

    if (empty($this->value)) {
      return !empty($this->definition['empty field name']) ? $this->definition['empty field name'] : t('Uncategorized');
    }

    if ($this->value === array(-1)) {
      return !empty($this->definition['invalid input']) ? $this->definition['invalid input'] : t('Invalid input');
    }

    return implode($this->operator == 'or' ? ' + ' : ', ', $this->titleQuery());
  }

  /**
   * Override for specific title lookups.
   */
  public function titleQuery() {
    return array_map('\Drupal\Component\Utility\String::checkPlain', array_combine($this->value, $this->value));
  }

  public function summaryName($data) {
    return $this->caseTransform(parent::summaryName($data), $this->options['case']);
  }

}
