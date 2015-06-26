<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\argument\ManyToOne.
 */

namespace Drupal\views\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ManyToOneHelper;

/**
 * An argument handler for use in fields that have a many to one relationship
 * with the table(s) to the left. This adds a bunch of options that are
 * reasonably common with this type of relationship.
 * Definition terms:
 * - numeric: If true, the field will be considered numeric. Probably should
 *   always be set TRUE as views_handler_argument_string has many to one
 *   capabilities.
 * - zero is null: If true, a 0 will be handled as empty, so for example
 *   a default argument can be provided or a summary can be shown.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("many_to_one")
 */
class ManyToOne extends ArgumentPluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\argument\ArgumentPluginBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->helper = new ManyToOneHelper($this);

    // Ensure defaults for these, during summaries and stuff:
    $this->operator = 'or';
    $this->value = array();
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    if (!empty($this->definition['numeric'])) {
      $options['break_phrase'] = array('default' => FALSE);
    }

    $options['add_table'] = array('default' => FALSE);
    $options['require_value'] = array('default' => FALSE);

    if (isset($this->helper)) {
      $this->helper->defineOptions($options);
    }
    else {
      $helper = new ManyToOneHelper($this);
      $helper->defineOptions($options);
    }

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

    $form['add_table'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow multiple filter values to work together'),
      '#description' => $this->t('If selected, multiple instances of this filter can work together, as though multiple values were supplied to the same filter. This setting is not compatible with the "Reduce duplicates" setting.'),
      '#default_value' => !empty($this->options['add_table']),
      '#fieldset' => 'more',
    );

    $form['require_value'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Do not display items with no value in summary'),
      '#default_value' => !empty($this->options['require_value']),
      '#fieldset' => 'more',
    );

    $this->helper->buildOptionsForm($form, $form_state);
  }

  /**
   * Override ensureMyTable so we can control how this joins in.
   * The operator actually has influence over joining.
   */
  public function ensureMyTable() {
    $this->helper->ensureMyTable();
  }

  public function query($group_by = FALSE) {
    $empty = FALSE;
    if (isset($this->definition['zero is null']) && $this->definition['zero is null']) {
      if (empty($this->argument)) {
        $empty = TRUE;
      }
    }
    else {
      if (!isset($this->argument)) {
        $empty = TRUE;
      }
    }
    if ($empty) {
      parent::ensureMyTable();
      $this->query->addWhere(0, "$this->tableAlias.$this->realField", NULL, 'IS NULL');
      return;
    }

    if (!empty($this->options['break_phrase'])) {
      $force_int = !empty($this->definition['numeric']);
      $this->unpackArgumentValue($force_int);
    }
    else {
      $this->value = array($this->argument);
      $this->operator = 'or';
    }

    $this->helper->addFilter();
  }

  function title() {
    if (!$this->argument) {
      return !empty($this->definition['empty field name']) ? $this->definition['empty field name'] : $this->t('Uncategorized');
    }

    if (!empty($this->options['break_phrase'])) {
      $force_int = !empty($this->definition['numeric']);
      $this->unpackArgumentValue($force_int);
    }
    else {
      $this->value = array($this->argument);
      $this->operator = 'or';
    }

    // @todo -- both of these should check definition for alternate keywords.

    if (empty($this->value)) {
      return !empty($this->definition['empty field name']) ? $this->definition['empty field name'] : $this->t('Uncategorized');
    }

    if ($this->value === array(-1)) {
      return !empty($this->definition['invalid input']) ? $this->definition['invalid input'] : $this->t('Invalid input');
    }

    return implode($this->operator == 'or' ? ' + ' : ', ', $this->titleQuery());
  }

  protected function summaryQuery() {
    $field = $this->table . '.' . $this->field;
    $join = $this->getJoin();

    if (!empty($this->options['require_value'])) {
      $join->type = 'INNER';
    }

    if (empty($this->options['add_table']) || empty($this->view->many_to_one_tables[$field])) {
      $this->tableAlias = $this->query->ensureTable($this->table, $this->relationship, $join);
    }
    else {
      $this->tableAlias = $this->helper->summaryJoin();
    }

    // Add the field.
    $this->base_alias = $this->query->addField($this->tableAlias, $this->realField);

    $this->summaryNameField();

    return $this->summaryBasics();
  }

  public function summaryArgument($data) {
    $value = $data->{$this->base_alias};
    if (empty($value)) {
      $value = 0;
    }

    return $value;
  }

  /**
   * Override for specific title lookups.
   */
  public function titleQuery() {
    return $this->value;
  }

}
