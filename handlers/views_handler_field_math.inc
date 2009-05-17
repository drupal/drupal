<?php

/**
 * @file
 * Definition of views_handler_field_math.
 */

/**
 * Render a mathematical expression as a numeric value
 *
 * Definition terms:
 * - float: If true this field contains a decimal value. If unset this field
 *   will be assumed to be integer.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_math extends views_handler_field_numeric {
  function option_definition() {
    $options = parent::option_definition();
    $options['expression'] = array('default' => '');

    return $options;
  }

  function options_form(&$form, &$form_state) {
    $form['expression'] = array(
      '#type' => 'textarea',
      '#title' => t('Expression'),
      '#description' => t('Enter mathematical expressions such as 2 + 2 or sqrt(5). You may assign variables and create mathematical functions and evaluate them. Use the ; to separate these. For example: f(x) = x + 2; f(2).'),
      '#default_value' => $this->options['expression'],
    );

    // Create a place for the help
    $form['expression_help'] = array();
    parent::options_form($form, $form_state);

    // Then move the existing help:
    $form['expression_help'] = $form['alter']['help'];
    unset($form['expression_help']['#dependency']);
    unset($form['alter']['help']);
  }

  function render($values) {
    ctools_include('math-expr');
    $tokens = array_map('floatval', $this->get_render_tokens(array()));
    $value = strtr($this->options['expression'], $tokens);
    $expressions = explode(';', $value);
    $math = new ctools_math_expr;
    foreach ($expressions as $expression) {
      if ($expression !== '') {
        $value = $math->evaluate($expression);
      }
    }

    // The rest is directly from views_handler_field_numeric but because it
    // does not allow the value to be passed in, it is copied.
    if (!empty($this->options['set_precision'])) {
      $value = number_format($value, $this->options['precision'], $this->options['decimal'], $this->options['separator']);
    }
    else {
      $remainder = abs($value) - intval(abs($value));
      $value = $value > 0 ? floor($value) : ceil($value);
      $value = number_format($value, 0, '', $this->options['separator']);
      if ($remainder) {
        // The substr may not be locale safe.
        $value .= $this->options['decimal'] . substr($remainder, 2);
      }
    }

    // Check to see if hiding should happen before adding prefix and suffix.
    if ($this->options['hide_empty'] && empty($value) && ($value !== 0 || $this->options['empty_zero'])) {
      return '';
    }

    // Should we format as a plural.
    if (!empty($this->options['format_plural']) && ($value != 0 || !$this->options['empty_zero'])) {
      $value = format_plural($value, $this->options['format_plural_singular'], $this->options['format_plural_plural']);
    }

    return $this->sanitize_value($this->options['prefix'] . $value . $this->options['suffix']);
  }

  function query() { }
}
