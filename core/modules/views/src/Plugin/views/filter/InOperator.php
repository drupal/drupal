<?php

namespace Drupal\views\Plugin\views\filter;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\Core\Form\OptGroup;

/**
 * Simple filter to handle matching of multiple options selectable via checkboxes
 *
 * Definition items:
 * - options callback: The function to call in order to generate the value options. If omitted, the options 'Yes' and 'No' will be used.
 * - options arguments: An array of arguments to pass to the options callback.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("in_operator")
 */
class InOperator extends FilterPluginBase {

  protected $valueFormType = 'checkboxes';

  /**
   * @var array
   * Stores all operations which are available on the form.
   */
  protected $valueOptions = NULL;

  /**
   * The filter title.
   *
   * @var string
   */
  protected $valueTitle;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->valueTitle = $this->t('Options');
    $this->valueOptions = NULL;
  }

  /**
   * Child classes should be used to override this function and set the
   * 'value options', unless 'options callback' is defined as a valid function
   * or static public method to generate these values.
   *
   * This can use a guard to be used to reduce database hits as much as
   * possible.
   *
   * @return array|null
   *   The stored values from $this->valueOptions.
   */
  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }

    if (isset($this->definition['options callback']) && is_callable($this->definition['options callback'])) {
      if (isset($this->definition['options arguments']) && is_array($this->definition['options arguments'])) {
        $this->valueOptions = call_user_func_array($this->definition['options callback'], $this->definition['options arguments']);
      }
      else {
        $this->valueOptions = call_user_func($this->definition['options callback']);
      }
    }
    else {
      $this->valueOptions = array(t('Yes'), $this->t('No'));
    }

    return $this->valueOptions;
  }

  public function defaultExposeOptions() {
    parent::defaultExposeOptions();
    $this->options['expose']['reduce'] = FALSE;
  }

  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);
    $form['expose']['reduce'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Limit list to selected items'),
      '#description' => $this->t('If checked, the only items presented to the user will be the ones selected here.'),
      '#default_value' => !empty($this->options['expose']['reduce']), // safety
    );
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['operator']['default'] = 'in';
    $options['value']['default'] = array();
    $options['expose']['contains']['reduce'] = array('default' => FALSE);

    return $options;
  }

  /**
   * This kind of construct makes it relatively easy for a child class
   * to add or remove functionality by overriding this function and
   * adding/removing items from this array.
   */
  function operators() {
    $operators = array(
      'in' => array(
        'title' => $this->t('Is one of'),
        'short' => $this->t('in'),
        'short_single' => $this->t('='),
        'method' => 'opSimple',
        'values' => 1,
      ),
      'not in' => array(
        'title' => $this->t('Is not one of'),
        'short' => $this->t('not in'),
        'short_single' => $this->t('<>'),
        'method' => 'opSimple',
        'values' => 1,
      ),
    );
    // if the definition allows for the empty operator, add it.
    if (!empty($this->definition['allow empty'])) {
      $operators += array(
        'empty' => array(
          'title' => $this->t('Is empty (NULL)'),
          'method' => 'opEmpty',
          'short' => $this->t('empty'),
          'values' => 0,
        ),
        'not empty' => array(
          'title' => $this->t('Is not empty (NOT NULL)'),
          'method' => 'opEmpty',
          'short' => $this->t('not empty'),
          'values' => 0,
        ),
      );
    }

    return $operators;
  }

  /**
   * Build strings from the operators() for 'select' options
   */
  public function operatorOptions($which = 'title') {
    $options = array();
    foreach ($this->operators() as $id => $info) {
      $options[$id] = $info[$which];
    }

    return $options;
  }

  protected function operatorValues($values = 1) {
    $options = array();
    foreach ($this->operators() as $id => $info) {
      if (isset($info['values']) && $info['values'] == $values) {
        $options[] = $id;
      }
    }

    return $options;
  }

  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = array();
    $options = array();

    $exposed = $form_state->get('exposed');
    if (!$exposed) {
      // Add a select all option to the value form.
      $options = array('all' => $this->t('Select all'));
    }

    $this->getValueOptions();
    $options += $this->valueOptions;
    $default_value = (array) $this->value;

    $which = 'all';
    if (!empty($form['operator'])) {
      $source = ':input[name="options[operator]"]';
    }
    if ($exposed) {
      $identifier = $this->options['expose']['identifier'];

      if (empty($this->options['expose']['use_operator']) || empty($this->options['expose']['operator_id'])) {
        // exposed and locked.
        $which = in_array($this->operator, $this->operatorValues(1)) ? 'value' : 'none';
      }
      else {
        $source = ':input[name="' . $this->options['expose']['operator_id'] . '"]';
      }

      if (!empty($this->options['expose']['reduce'])) {
        $options = $this->reduceValueOptions();

        if (!empty($this->options['expose']['multiple']) && empty($this->options['expose']['required'])) {
          $default_value = array();
        }
      }

      if (empty($this->options['expose']['multiple'])) {
        if (empty($this->options['expose']['required']) && (empty($default_value) || !empty($this->options['expose']['reduce'])) || isset($this->options['value']['all'])) {
          $default_value = 'All';
        }
        elseif (empty($default_value)) {
          $keys = array_keys($options);
          $default_value = array_shift($keys);
        }
        else {
          $copy = $default_value;
          $default_value = array_shift($copy);
        }
      }
    }

    if ($which == 'all' || $which == 'value') {
      $form['value'] = array(
        '#type' => $this->valueFormType,
        '#title' => $this->valueTitle,
        '#options' => $options,
        '#default_value' => $default_value,
        // These are only valid for 'select' type, but do no harm to checkboxes.
        '#multiple' => TRUE,
        '#size' => count($options) > 8 ? 8 : count($options),
      );
      $user_input = $form_state->getUserInput();
      if ($exposed && !isset($user_input[$identifier])) {
        $user_input[$identifier] = $default_value;
        $form_state->setUserInput($user_input);
      }

      if ($which == 'all') {
        if (!$exposed && (in_array($this->valueFormType, ['checkbox', 'checkboxes', 'radios', 'select']))) {
          $form['value']['#prefix'] = '<div id="edit-options-value-wrapper">';
          $form['value']['#suffix'] = '</div>';
        }
        // Setup #states for all operators with one value.
        foreach ($this->operatorValues(1) as $operator) {
          $form['value']['#states']['visible'][] = array(
            $source => array('value' => $operator),
          );
        }
      }
    }
  }

  /**
   * When using exposed filters, we may be required to reduce the set.
   */
  public function reduceValueOptions($input = NULL) {
    if (!isset($input)) {
      $input = $this->valueOptions;
    }

    // Because options may be an array of strings, or an array of mixed arrays
    // and strings (optgroups) or an array of objects, we have to
    // step through and handle each one individually.
    $options = array();
    foreach ($input as $id => $option) {
      if (is_array($option)) {
        $options[$id] = $this->reduceValueOptions($option);
        continue;
      }
      elseif (is_object($option)) {
        $keys = array_keys($option->option);
        $key = array_shift($keys);
        if (isset($this->options['value'][$key])) {
          $options[$id] = $option;
        }
      }
      elseif (isset($this->options['value'][$id])) {
        $options[$id] = $option;
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    if (empty($this->options['exposed'])) {
      return TRUE;
    }

    // The "All" state for this type of filter could have a default value. If
    // this is a non-multiple and non-required option, then this filter will
    // participate by using the default settings *if* 'limit' is true.
    if (empty($this->options['expose']['multiple']) && empty($this->options['expose']['required']) && !empty($this->options['expose']['limit'])) {
      $identifier = $this->options['expose']['identifier'];
      if ($input[$identifier] == 'All') {
        return TRUE;
      }
    }

    return parent::acceptExposedInput($input);
  }

  protected function valueSubmit($form, FormStateInterface $form_state) {
    // Drupal's FAPI system automatically puts '0' in for any checkbox that
    // was not set, and the key to the checkbox if it is set.
    // Unfortunately, this means that if the key to that checkbox is 0,
    // we are unable to tell if that checkbox was set or not.

    // Luckily, the '#value' on the checkboxes form actually contains
    // *only* a list of checkboxes that were set, and we can use that
    // instead.

    $form_state->setValue(array('options', 'value'), $form['value']['#value']);
  }

  public function adminSummary() {
    if ($this->isAGroup()) {
      return $this->t('grouped');
    }
    if (!empty($this->options['exposed'])) {
      return $this->t('exposed');
    }
    $info = $this->operators();

    $this->getValueOptions();
    // Some filter_in_operator usage uses optgroups forms, so flatten it.
    $flat_options = OptGroup::flattenOptions($this->valueOptions);

    if (!is_array($this->value)) {
      return;
    }

    $operator = $info[$this->operator]['short'];
    $values = '';
    if (in_array($this->operator, $this->operatorValues(1))) {
      // Remove every element which is not known.
      foreach ($this->value as $value) {
        if (!isset($flat_options[$value])) {
          unset($this->value[$value]);
        }
      }
      // Choose different kind of output for 0, a single and multiple values.
      if (count($this->value) == 0) {
        $values = $this->t('Unknown');
      }
      elseif (count($this->value) == 1) {
        // If any, use the 'single' short name of the operator instead.
        if (isset($info[$this->operator]['short_single'])) {
          $operator = $info[$this->operator]['short_single'];
        }

        $keys = $this->value;
        $value = array_shift($keys);
        if (isset($flat_options[$value])) {
          $values = $flat_options[$value];
        }
        else {
          $values = '';
        }
      }
      else {
        foreach ($this->value as $value) {
          if ($values !== '') {
            $values .= ', ';
          }
          if (Unicode::strlen($values) > 8) {
            $values = Unicode::truncate($values, 8, FALSE, TRUE);
            break;
          }
          if (isset($flat_options[$value])) {
            $values .= $flat_options[$value];
          }
        }
      }
    }

    return $operator . (($values !== '') ? ' ' . $values : '');
  }

  public function query() {
    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}();
    }
  }

  protected function opSimple() {
    if (empty($this->value)) {
      return;
    }
    $this->ensureMyTable();

    // We use array_values() because the checkboxes keep keys and that can cause
    // array addition problems.
    $this->query->addWhere($this->options['group'], "$this->tableAlias.$this->realField", array_values($this->value), $this->operator);
  }

  protected function opEmpty() {
    $this->ensureMyTable();
    if ($this->operator == 'empty') {
      $operator = "IS NULL";
    }
    else {
      $operator = "IS NOT NULL";
    }

    $this->query->addWhere($this->options['group'], "$this->tableAlias.$this->realField", NULL, $operator);
  }

  public function validate() {
    $this->getValueOptions();
    $errors = parent::validate();

    // If the operator is an operator which doesn't require a value, there is
    // no need for additional validation.
    if (in_array($this->operator, $this->operatorValues(0))) {
      return array();
    }

    if (!in_array($this->operator, $this->operatorValues(1))) {
      $errors[] = $this->t('The operator is invalid on filter: @filter.', array('@filter' => $this->adminLabel(TRUE)));
    }
    if (is_array($this->value)) {
      if (!isset($this->valueOptions)) {
        // Don't validate if there are none value options provided, for example for special handlers.
        return $errors;
      }
      if ($this->options['exposed'] && !$this->options['expose']['required'] && empty($this->value)) {
        // Don't validate if the field is exposed and no default value is provided.
        return $errors;
      }

      // Some filter_in_operator usage uses optgroups forms, so flatten it.
      $flat_options = OptGroup::flattenOptions($this->valueOptions);

      // Remove every element which is not known.
      foreach ($this->value as $value) {
        if (!isset($flat_options[$value])) {
          unset($this->value[$value]);
        }
      }
      // Choose different kind of output for 0, a single and multiple values.
      if (count($this->value) == 0) {
        $errors[] = $this->t('No valid values found on filter: @filter.', array('@filter' => $this->adminLabel(TRUE)));
      }
    }
    elseif (!empty($this->value) && ($this->operator == 'in' || $this->operator == 'not in')) {
      $errors[] = $this->t('The value @value is not an array for @operator on filter: @filter', array('@value' => var_export($this->value), '@operator' => $this->operator, '@filter' => $this->adminLabel(TRUE)));
    }
    return $errors;
  }

}
