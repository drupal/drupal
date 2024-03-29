<?php

namespace Drupal\views\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsFilter;

/**
 * Simple filter to handle greater than/less than filters.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("numeric")]
class NumericFilter extends FilterPluginBase implements FilterOperatorsInterface {

  protected $alwaysMultiple = TRUE;

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['value'] = [
      'contains' => [
        'min' => ['default' => ''],
        'max' => ['default' => ''],
        'value' => ['default' => ''],
      ],
    ];

    $options['expose']['contains']['placeholder'] = ['default' => ''];
    $options['expose']['contains']['min_placeholder'] = ['default' => ''];
    $options['expose']['contains']['max_placeholder'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultExposeOptions() {
    parent::defaultExposeOptions();
    $this->options['expose']['min_placeholder'] = NULL;
    $this->options['expose']['max_placeholder'] = NULL;
    $this->options['expose']['placeholder'] = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);

    $form['expose']['min_placeholder'] = [
      '#type' => 'textfield',
      '#default_value' => $this->options['expose']['min_placeholder'],
      '#title' => $this->t('Min placeholder'),
      '#size' => 40,
      '#description' => $this->t('Hint text that appears inside the Min field when empty.'),
    ];
    $form['expose']['max_placeholder'] = [
      '#type' => 'textfield',
      '#default_value' => $this->options['expose']['max_placeholder'],
      '#title' => $this->t('Max placeholder'),
      '#size' => 40,
      '#description' => $this->t('Hint text that appears inside the Max field when empty.'),
    ];
    // Setup #states for all operators with two value.
    $states = [[':input[name="options[expose][use_operator]"]' => ['checked' => TRUE]]];
    foreach ($this->operatorValues(2) as $operator) {
      $states[] = [
        ':input[name="options[operator]"]' => ['value' => $operator],
      ];
    }
    $form['expose']['min_placeholder']['#states']['visible'] = $states;
    $form['expose']['max_placeholder']['#states']['visible'] = $states;

    $form['expose']['placeholder'] = [
      '#type' => 'textfield',
      '#default_value' => $this->options['expose']['placeholder'],
      '#title' => $this->t('Placeholder'),
      '#size' => 40,
      '#description' => $this->t('Hint text that appears inside the field when empty.'),
    ];
    // Setup #states for all operators with one value.
    $form['expose']['placeholder']['#states']['visible'] = [[':input[name="options[expose][use_operator]"]' => ['checked' => TRUE]]];
    foreach ($this->operatorValues(1) as $operator) {
      $form['expose']['placeholder']['#states']['visible'][] = [
        ':input[name="options[operator]"]' => ['value' => $operator],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function operators() {
    $operators = [
      '<' => [
        'title' => $this->t('Is less than'),
        'method' => 'opSimple',
        'short' => $this->t('<'),
        'values' => 1,
      ],
      '<=' => [
        'title' => $this->t('Is less than or equal to'),
        'method' => 'opSimple',
        'short' => $this->t('<='),
        'values' => 1,
      ],
      '=' => [
        'title' => $this->t('Is equal to'),
        'method' => 'opSimple',
        'short' => $this->t('='),
        'values' => 1,
      ],
      '!=' => [
        'title' => $this->t('Is not equal to'),
        'method' => 'opSimple',
        'short' => $this->t('!='),
        'values' => 1,
      ],
      '>=' => [
        'title' => $this->t('Is greater than or equal to'),
        'method' => 'opSimple',
        'short' => $this->t('>='),
        'values' => 1,
      ],
      '>' => [
        'title' => $this->t('Is greater than'),
        'method' => 'opSimple',
        'short' => $this->t('>'),
        'values' => 1,
      ],
      'between' => [
        'title' => $this->t('Is between'),
        'method' => 'opBetween',
        'short' => $this->t('between'),
        'values' => 2,
      ],
      'not between' => [
        'title' => $this->t('Is not between'),
        'method' => 'opBetween',
        'short' => $this->t('not between'),
        'values' => 2,
      ],
      'regular_expression' => [
        'title' => $this->t('Regular expression'),
        'short' => $this->t('regex'),
        'method' => 'opRegex',
        'values' => 1,
      ],
      'not_regular_expression' => [
        'title' => $this->t('Negated regular expression'),
        'short' => $this->t('not regex'),
        'method' => 'opNotRegex',
        'values' => 1,
      ],
    ];

    // if the definition allows for the empty operator, add it.
    if (!empty($this->definition['allow empty'])) {
      $operators += [
        'empty' => [
          'title' => $this->t('Is empty (NULL)'),
          'method' => 'opEmpty',
          'short' => $this->t('empty'),
          'values' => 0,
        ],
        'not empty' => [
          'title' => $this->t('Is not empty (NOT NULL)'),
          'method' => 'opEmpty',
          'short' => $this->t('not empty'),
          'values' => 0,
        ],
      ];
    }

    return $operators;
  }

  /**
   * Provide a list of all the numeric operators.
   */
  public function operatorOptions($which = 'title') {
    $options = [];
    foreach ($this->operators() as $id => $info) {
      $options[$id] = $info[$which];
    }

    return $options;
  }

  protected function operatorValues($values = 1) {
    $options = [];
    foreach ($this->operators() as $id => $info) {
      if ($info['values'] == $values) {
        $options[] = $id;
      }
    }

    return $options;
  }

  /**
   * Provide a simple textfield for equality.
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value']['#tree'] = TRUE;

    // We have to make some choices when creating this as an exposed
    // filter form. For example, if the operator is locked and thus
    // not rendered, we can't render dependencies; instead we only
    // render the form items we need.
    $which = 'all';
    if (!empty($form['operator'])) {
      $source = ':input[name="options[operator]"]';
    }

    if ($exposed = $form_state->get('exposed')) {
      $identifier = $this->options['expose']['identifier'];

      if (empty($this->options['expose']['use_operator']) || empty($this->options['expose']['operator_id'])) {
        // exposed and locked.
        $which = in_array($this->operator, $this->operatorValues(2)) ? 'minmax' : 'value';
      }
      else {
        $source = ':input[name="' . $this->options['expose']['operator_id'] . '"]';
      }
    }

    $user_input = $form_state->getUserInput();
    if ($which == 'all') {
      $form['value']['value'] = [
        '#type' => 'textfield',
        '#title' => !$exposed ? $this->t('Value') : '',
        '#size' => 30,
        '#default_value' => $this->value['value'],
      ];
      if (!empty($this->options['expose']['placeholder'])) {
        $form['value']['value']['#attributes']['placeholder'] = $this->options['expose']['placeholder'];
      }
      // Setup #states for all operators with one value.
      foreach ($this->operatorValues(1) as $operator) {
        $form['value']['value']['#states']['visible'][] = [
          $source => ['value' => $operator],
        ];
      }
      if ($exposed && !isset($user_input[$identifier]['value'])) {
        $user_input[$identifier]['value'] = $this->value['value'];
        $form_state->setUserInput($user_input);
      }
    }
    elseif ($which == 'value') {
      // When exposed we drop the value-value and just do value if
      // the operator is locked.
      $form['value'] = [
        '#type' => 'textfield',
        '#title' => !$exposed ? $this->t('Value') : '',
        '#size' => 30,
        '#default_value' => $this->value['value'],
      ];
      if (!empty($this->options['expose']['placeholder'])) {
        $form['value']['#attributes']['placeholder'] = $this->options['expose']['placeholder'];
      }
      if ($exposed && !isset($user_input[$identifier])) {
        $user_input[$identifier] = $this->value['value'];
        $form_state->setUserInput($user_input);
      }
    }

    // Minimum and maximum form fields are associated to some specific operators
    // like 'between'. Ensure that min and max fields are only visible if
    // the associated operator is not excluded from the operator list.
    $two_value_operators_available = ($which == 'all' || $which == 'minmax');

    if (!empty($this->options['expose']['operator_limit_selection']) &&
        !empty($this->options['expose']['operator_list'])) {
      $two_value_operators_available = FALSE;
      foreach ($this->options['expose']['operator_list'] as $operator) {
        if (in_array($operator, $this->operatorValues(2), TRUE)) {
          $two_value_operators_available = TRUE;
          break;
        }
      }
    }

    if ($two_value_operators_available) {
      $form['value']['min'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Min'),
        '#size' => 30,
        '#default_value' => $this->value['min'],
      ];
      if (!empty($this->options['expose']['min_placeholder'])) {
        $form['value']['min']['#attributes']['placeholder'] = $this->options['expose']['min_placeholder'];
      }
      $form['value']['max'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Max'),
        '#size' => 30,
        '#default_value' => $this->value['max'],
      ];
      if (!empty($this->options['expose']['max_placeholder'])) {
        $form['value']['max']['#attributes']['placeholder'] = $this->options['expose']['max_placeholder'];
      }
      if ($which == 'all') {
        $states = [];
        // Setup #states for all operators with two values.
        foreach ($this->operatorValues(2) as $operator) {
          $states['#states']['visible'][] = [
            $source => ['value' => $operator],
          ];
        }
        $form['value']['min'] += $states;
        $form['value']['max'] += $states;
      }
      if ($exposed && !isset($user_input[$identifier]['min'])) {
        $user_input[$identifier]['min'] = $this->value['min'];
      }
      if ($exposed && !isset($user_input[$identifier]['max'])) {
        $user_input[$identifier]['max'] = $this->value['max'];
      }

      if (!isset($form['value'])) {
        // Ensure there is something in the 'value'.
        $form['value'] = [
          '#type' => 'value',
          '#value' => NULL,
        ];
      }
    }
  }

  public function query() {
    $this->ensureMyTable();
    $field = "$this->tableAlias.$this->realField";

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($field);
    }
  }

  /**
   * Filters by operator between.
   *
   * @param object $field
   *   The views field.
   */
  protected function opBetween($field) {
    if (is_numeric($this->value['min']) && is_numeric($this->value['max'])) {
      $operator = $this->operator == 'between' ? 'BETWEEN' : 'NOT BETWEEN';
      $this->query->addWhere($this->options['group'], $field, [$this->value['min'], $this->value['max']], $operator);
    }
    elseif (is_numeric($this->value['min'])) {
      $operator = $this->operator == 'between' ? '>=' : '<';
      $this->query->addWhere($this->options['group'], $field, $this->value['min'], $operator);
    }
    elseif (is_numeric($this->value['max'])) {
      $operator = $this->operator == 'between' ? '<=' : '>';
      $this->query->addWhere($this->options['group'], $field, $this->value['max'], $operator);
    }
  }

  protected function opSimple($field) {
    $this->query->addWhere($this->options['group'], $field, $this->value['value'], $this->operator);
  }

  protected function opEmpty($field) {
    if ($this->operator == 'empty') {
      $operator = "IS NULL";
    }
    else {
      $operator = "IS NOT NULL";
    }

    $this->query->addWhere($this->options['group'], $field, NULL, $operator);
  }

  /**
   * Filters by a regular expression.
   *
   * @param string $field
   *   The expression pointing to the queries field, for example "foo.bar".
   */
  protected function opRegex($field) {
    $this->query->addWhere($this->options['group'], $field, $this->value['value'], 'REGEXP');
  }

  /**
   * Filters by a negated regular expression.
   *
   * @param string $field
   *   The expression pointing to the queries field, for example "foo.bar".
   */
  protected function opNotRegex($field) {
    $this->query->addWhere($this->options['group'], $field, $this->value['value'], 'NOT REGEXP');
  }

  public function adminSummary() {
    if ($this->isAGroup()) {
      return $this->t('grouped');
    }
    if (!empty($this->options['exposed'])) {
      return $this->t('exposed');
    }

    $options = $this->operatorOptions('short');
    $output = $options[$this->operator];
    if (in_array($this->operator, $this->operatorValues(2))) {
      $output .= ' ' . $this->t('@min and @max', ['@min' => $this->value['min'], '@max' => $this->value['max']]);
    }
    elseif (in_array($this->operator, $this->operatorValues(1))) {
      $output .= ' ' . $this->value['value'];
    }
    return $output;
  }

  /**
   * Do some minor translation of the exposed input.
   */
  public function acceptExposedInput($input) {
    if (empty($this->options['exposed'])) {
      return TRUE;
    }

    // Rewrite the input value so that it's in the correct format so that
    // the parent gets the right data.
    $key = $this->isAGroup() ? 'group_info' : 'expose';
    if (empty($this->options[$key]['identifier'])) {
      // Invalid identifier configuration. Value can't be resolved.
      return FALSE;
    }
    $value = &$input[$this->options[$key]['identifier']];
    if (!is_array($value)) {
      $value = [
        'value' => $value,
      ];
    }

    $rc = parent::acceptExposedInput($input);

    if (empty($this->options['expose']['required'])) {
      // We have to do some of our own checking for non-required filters.
      $info = $this->operators();
      if (!empty($info[$this->operator]['values'])) {
        switch ($info[$this->operator]['values']) {
          case 1:
            if ($value['value'] === '') {
              return FALSE;
            }
            break;

          case 2:
            if ($value['min'] === '' && $value['max'] === '') {
              return FALSE;
            }
            break;
        }
      }
    }

    return $rc;
  }

}
