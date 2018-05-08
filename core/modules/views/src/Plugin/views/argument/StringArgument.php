<?php

namespace Drupal\views\Plugin\views\argument;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ManyToOneHelper;

/**
 * Basic argument handler to implement string arguments that may have length
 * limits.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("string")
 */
class StringArgument extends ArgumentPluginBase {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    if (!empty($this->definition['many to one'])) {
      $this->helper = new ManyToOneHelper($this);

      // Ensure defaults for these, during summaries and stuff:
      $this->operator = 'or';
      $this->value = [];
    }
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['glossary'] = ['default' => FALSE];
    $options['limit'] = ['default' => 0];
    $options['case'] = ['default' => 'none'];
    $options['path_case'] = ['default' => 'none'];
    $options['transform_dash'] = ['default' => FALSE];
    $options['break_phrase'] = ['default' => FALSE];

    if (!empty($this->definition['many to one'])) {
      $options['add_table'] = ['default' => FALSE];
      $options['require_value'] = ['default' => FALSE];
    }

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['glossary'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Glossary mode'),
      '#description' => $this->t('Glossary mode applies a limit to the number of characters used in the filter value, which allows the summary view to act as a glossary.'),
      '#default_value' => $this->options['glossary'],
      '#group' => 'options][more',
    ];

    $form['limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Character limit'),
      '#description' => $this->t('How many characters of the filter value to filter against. If set to 1, all fields starting with the first letter in the filter value would be matched.'),
      '#default_value' => $this->options['limit'],
      '#states' => [
        'visible' => [
          ':input[name="options[glossary]"]' => ['checked' => TRUE],
        ],
      ],
      '#group' => 'options][more',
    ];

    $form['case'] = [
      '#type' => 'select',
      '#title' => $this->t('Case'),
      '#description' => $this->t('When printing the title and summary, how to transform the case of the filter value.'),
      '#options' => [
        'none' => $this->t('No transform'),
        'upper' => $this->t('Upper case'),
        'lower' => $this->t('Lower case'),
        'ucfirst' => $this->t('Capitalize first letter'),
        'ucwords' => $this->t('Capitalize each word'),
      ],
      '#default_value' => $this->options['case'],
      '#group' => 'options][more',
    ];

    $form['path_case'] = [
      '#type' => 'select',
      '#title' => $this->t('Case in path'),
      '#description' => $this->t('When printing URL paths, how to transform the case of the filter value. Do not use this unless with Postgres as it uses case sensitive comparisons.'),
      '#options' => [
        'none' => $this->t('No transform'),
        'upper' => $this->t('Upper case'),
        'lower' => $this->t('Lower case'),
        'ucfirst' => $this->t('Capitalize first letter'),
        'ucwords' => $this->t('Capitalize each word'),
      ],
      '#default_value' => $this->options['path_case'],
      '#group' => 'options][more',
    ];

    $form['transform_dash'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Transform spaces to dashes in URL'),
      '#default_value' => $this->options['transform_dash'],
      '#group' => 'options][more',
    ];

    if (!empty($this->definition['many to one'])) {
      $form['add_table'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Allow multiple filter values to work together'),
        '#description' => $this->t('If selected, multiple instances of this filter can work together, as though multiple values were supplied to the same filter. This setting is not compatible with the "Reduce duplicates" setting.'),
        '#default_value' => !empty($this->options['add_table']),
        '#group' => 'options][more',
      ];

      $form['require_value'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Do not display items with no value in summary'),
        '#default_value' => !empty($this->options['require_value']),
        '#group' => 'options][more',
      ];
    }

    // allow + for or, , for and
    $form['break_phrase'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow multiple values'),
      '#description' => $this->t('If selected, users can enter multiple values in the form of 1+2+3 (for OR) or 1,2,3 (for AND).'),
      '#default_value' => !empty($this->options['break_phrase']),
      '#group' => 'options][more',
    ];
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
    $formula = "SUBSTRING($this->tableAlias.$this->realField, 1, " . intval($this->options['limit']) . ")";

    if ($this->options['case'] != 'none') {
      // Support case-insensitive substring comparisons for SQLite by using the
      // 'NOCASE_UTF8' collation.
      // @see Drupal\Core\Database\Driver\sqlite\Connection::open()
      if (Database::getConnection()->databaseType() == 'sqlite') {
        $formula .= ' COLLATE NOCASE_UTF8';
      }

      // Support case-insensitive substring comparisons for PostgreSQL by
      // converting the formula to lowercase.
      if (Database::getConnection()->databaseType() == 'pgsql') {
        $formula = 'LOWER(' . $formula . ')';
      }
    }
    return $formula;
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
      $this->unpackArgumentValue();
    }
    else {
      $this->value = [$argument];
      $this->operator = 'or';
    }

    // Support case-insensitive substring comparisons for PostgreSQL by
    // converting the arguments to lowercase.
    if ($this->options['case'] != 'none' && Database::getConnection()->databaseType() == 'pgsql') {
      foreach ($this->value as $key => $value) {
        $this->value[$key] = mb_strtolower($value);
      }
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
      $placeholders = [
        $placeholder => $argument,
      ];
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
    return $this->t('Alphabetical', [], ['context' => 'Sort order']);
  }

  public function title() {
    // Support case-insensitive title comparisons for PostgreSQL by converting
    // the title to lowercase.
    if ($this->options['case'] != 'none' && Database::getConnection()->databaseType() == 'pgsql') {
      $this->options['case'] = 'lower';
    }

    $this->argument = $this->caseTransform($this->argument, $this->options['case']);
    if (!empty($this->options['transform_dash'])) {
      $this->argument = strtr($this->argument, '-', ' ');
    }

    if (!empty($this->options['break_phrase'])) {
      $this->unpackArgumentValue();
    }
    else {
      $this->value = [$this->argument];
      $this->operator = 'or';
    }

    if (empty($this->value)) {
      return !empty($this->definition['empty field name']) ? $this->definition['empty field name'] : $this->t('Uncategorized');
    }

    if ($this->value === [-1]) {
      return !empty($this->definition['invalid input']) ? $this->definition['invalid input'] : $this->t('Invalid input');
    }

    return implode($this->operator == 'or' ? ' + ' : ', ', $this->titleQuery());
  }

  /**
   * Override for specific title lookups.
   */
  public function titleQuery() {
    return $this->value;
  }

  public function summaryName($data) {
    return $this->caseTransform(parent::summaryName($data), $this->options['case']);
  }

  /**
   * {@inheritdoc}
   */
  public function getContextDefinition() {
    if ($context_definition = parent::getContextDefinition()) {
      return $context_definition;
    }

    // If the parent does not provide a context definition through the
    // validation plugin, fall back to the string type.
    return new ContextDefinition('string', $this->adminLabel(), FALSE);
  }

}
