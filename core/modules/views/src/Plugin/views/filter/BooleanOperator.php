<?php

namespace Drupal\views\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Simple filter to handle matching of boolean values.
 *
 * Definition items:
 * - label: (REQUIRED) The label for the checkbox.
 * - type: For basic 'true false' types, an item can specify the following:
 *    - true-false: True/false (this is the default)
 *    - yes-no: Yes/No
 *    - on-off: On/Off
 *    - enabled-disabled: Enabled/Disabled
 * - accept null: Treat a NULL value as false.
 * - use_equal: If you use this flag the query will use = 1 instead of <> 0.
 *   This might be helpful for performance reasons.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("boolean")]
class BooleanOperator extends FilterPluginBase implements FilterOperatorsInterface {

  /**
   * The equal query operator.
   *
   * @var string
   */
  const EQUAL = '=';

  /**
   * The non equal query operator.
   *
   * @var string
   */
  const NOT_EQUAL = '<>';

  /**
   * Exposed filter options.
   *
   * @var bool
   */
  protected $alwaysMultiple = TRUE;

  /**
   * Whether to accept NULL as a false value or not.
   *
   * @var bool
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  public $accept_null = FALSE;

  /**
   * The value title.
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  public string $value_value;

  /**
   * The value options.
   */
  public ?array $valueOptions;

  /**
   * {@inheritdoc}
   */
  public function operatorOptions($which = 'title') {
    $options = [];
    foreach ($this->operators() as $id => $info) {
      $options[$id] = $info[$which];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function operators() {
    return [
      '=' => [
        'title' => $this->t('Is equal to'),
        'method' => 'queryOpBoolean',
        'short' => $this->t('='),
        'values' => 1,
        'query_operator' => self::EQUAL,
      ],
      '!=' => [
        'title' => $this->t('Is not equal to'),
        'method' => 'queryOpBoolean',
        'short' => $this->t('!='),
        'values' => 1,
        'query_operator' => self::NOT_EQUAL,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, ?array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->value_value = $this->t('True');

    if (isset($this->definition['label'])) {
      $this->value_value = $this->definition['label'];
    }
    elseif (isset($this->definition['title'])) {
      $this->value_value = $this->definition['title'];
    }

    if (isset($this->definition['accept null'])) {
      $this->accept_null = (bool) $this->definition['accept null'];
    }
    elseif (isset($this->definition['accept_null'])) {
      $this->accept_null = (bool) $this->definition['accept_null'];
    }
    $this->valueOptions = NULL;
  }

  /**
   * Return the possible options for this filter.
   *
   * Child classes should override this function to set the possible values
   * for the filter.  Since this is a boolean filter, the array should have
   * two possible keys: 1 for "True" and 0 for "False", although the labels
   * can be whatever makes sense for the filter.  These values are used for
   * configuring the filter, when the filter is exposed, and in the admin
   * summary of the filter.  Normally, this should be static data, but if it's
   * dynamic for some reason, child classes should use a guard to reduce
   * database hits as much as possible.
   */
  public function getValueOptions() {
    if (isset($this->definition['type'])) {
      if ($this->definition['type'] == 'yes-no') {
        $this->valueOptions = [1 => $this->t('Yes'), 0 => $this->t('No')];
      }
      if ($this->definition['type'] == 'on-off') {
        $this->valueOptions = [1 => $this->t('On'), 0 => $this->t('Off')];
      }
      if ($this->definition['type'] == 'enabled-disabled') {
        $this->valueOptions = [1 => $this->t('Enabled'), 0 => $this->t('Disabled')];
      }
    }

    // Provide a fallback if the above didn't set anything.
    if (!isset($this->valueOptions)) {
      $this->valueOptions = [1 => $this->t('True'), 0 => $this->t('False')];
    }

    return $this->valueOptions;
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['value']['default'] = FALSE;

    return $options;
  }

  protected function valueForm(&$form, FormStateInterface $form_state) {
    if (empty($this->valueOptions)) {
      // Initialize the array of possible values for this filter.
      $this->getValueOptions();
    }
    if ($exposed = $form_state->get('exposed')) {
      // Exposed filter: use a select box to save space.
      $filter_form_type = 'select';
    }
    else {
      // Configuring a filter: use radios for clarity.
      $filter_form_type = 'radios';
    }
    $form['value'] = [
      '#type' => $filter_form_type,
      '#title' => $this->value_value,
      '#options' => $this->valueOptions,
      '#default_value' => $this->value,
    ];
    if (!empty($this->options['exposed'])) {
      $identifier = $this->options['expose']['identifier'];
      $user_input = $form_state->getUserInput();
      if ($exposed && !isset($user_input[$identifier])) {
        $user_input[$identifier] = $this->value;
        $form_state->setUserInput($user_input);
      }
      // If we're configuring an exposed filter, add an - Any - option.
      if (!$exposed || empty($this->options['expose']['required'])) {
        $form['value']['#options'] = ['All' => $this->t('- Any -')] + $form['value']['#options'];
      }
    }
  }

  protected function valueValidate($form, FormStateInterface $form_state) {
    if ($form_state->getValue(['options', 'value']) == 'All' && !$form_state->isValueEmpty(['options', 'expose', 'required'])) {
      $form_state->setErrorByName('value', $this->t('You must select a value unless this is an non-required exposed filter.'));
    }
  }

  public function adminSummary() {
    if ($this->isAGroup()) {
      return $this->t('grouped');
    }
    if (!empty($this->options['exposed'])) {
      return $this->t('exposed');
    }
    if (empty($this->valueOptions)) {
      $this->getValueOptions();
    }
    // Now that we have the valid options for this filter, just return the
    // human-readable label based on the current value.  The valueOptions
    // array is keyed with either 0 or 1, so if the current value is not
    // empty, use the label for 1, and if it's empty, use the label for 0.
    return $this->operator . ' ' . $this->valueOptions[!empty($this->value)];
  }

  public function defaultExposeOptions() {
    parent::defaultExposeOptions();
    $this->options['expose']['operator_id'] = '';
    $this->options['expose']['label'] = $this->value_value;
    $this->options['expose']['required'] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $field = "$this->tableAlias.$this->realField";

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      call_user_func([$this, $info[$this->operator]['method']], $field, $info[$this->operator]['query_operator']);
    }
  }

  /**
   * Adds a where condition to the query for a boolean value.
   *
   * @param string $field
   *   The field name to add the where condition for.
   * @param string $query_operator
   *   (optional) Either self::EQUAL or self::NOT_EQUAL. Defaults to
   *   self::EQUAL.
   */
  protected function queryOpBoolean($field, $query_operator = self::EQUAL) {
    if (empty($this->value)) {
      if ($this->accept_null) {
        if ($query_operator === self::EQUAL) {
          $condition = ($this->query->getConnection()->condition('OR'))
            ->condition($field, 0, $query_operator)
            ->isNull($field);
        }
        else {
          $condition = ($this->query->getConnection()->condition('AND'))
            ->condition($field, 0, $query_operator)
            ->isNotNull($field);
        }
        $this->query->addWhere($this->options['group'], $condition);
      }
      else {
        $this->query->addWhere($this->options['group'], $field, 0, $query_operator);
      }
    }
    else {
      if (!empty($this->definition['use_equal'])) {
        // Forces a self::EQUAL operator instead of a self::NOT_EQUAL for
        // performance reasons.
        if ($query_operator === self::EQUAL) {
          $this->query->addWhere($this->options['group'], $field, 1, self::EQUAL);
        }
        else {
          $this->query->addWhere($this->options['group'], $field, 0, self::EQUAL);
        }
      }
      else {
        $this->query->addWhere($this->options['group'], $field, 1, $query_operator);
      }
    }
  }

}
