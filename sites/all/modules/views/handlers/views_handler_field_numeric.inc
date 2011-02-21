<?php
// $Id: views_handler_field_numeric.inc,v 1.6.4.4 2010/11/05 07:20:54 dereine Exp $
/**
 * Render a field as a numeric value
 *
 * Definition terms:
 * - float: If true this field contains a decimal value. If unset this field
 *          will be assumed to be integer.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_numeric extends views_handler_field {
  function option_definition() {
    $options = parent::option_definition();

    $options['set_precision'] = array('default' => FALSE);
    $options['precision'] = array('default' => 0);
    $options['decimal'] = array('default' => '.', 'translatable' => TRUE);
    $options['separator'] = array('default' => ',', 'translatable' => TRUE);
    $options['format_plural'] = array('default' => FALSE);
    $options['format_plural_singular'] = array('default' => '1');
    $options['format_plural_plural'] = array('default' => '@count');
    $options['prefix'] = array('default' => '', 'translatable' => TRUE);
    $options['suffix'] = array('default' => '', 'translatable' => TRUE);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    if (!empty($this->definition['float'])) {
      $form['set_precision'] = array(
        '#type' => 'checkbox',
        '#title' => t('Round'),
        '#description' => t('If checked, the number will be rounded.'),
        '#default_value' => $this->options['set_precision'],
      );
      $form['precision'] = array(
        '#type' => 'textfield',
        '#title' => t('Precision'),
        '#default_value' => $this->options['precision'],
        '#description' => t('Specify how many digits to print after the decimal point.'),
        '#process' => array('ctools_dependent_process'),
        '#dependency' => array('edit-options-set-precision' => array(TRUE)),
        '#size' => 2,
      );
      $form['decimal'] = array(
        '#type' => 'textfield',
        '#title' => t('Decimal point'),
        '#default_value' => $this->options['decimal'],
        '#description' => t('What single character to use as a decimal point.'),
        '#size' => 2,
      );
    }
    $form['separator'] = array(
      '#type' => 'textfield',
      '#title' => t('Thousands separator'),
      '#default_value' => $this->options['separator'],
      '#description' => t('What single character to use as the thousands separator.'),
      '#size' => 2,
    );
    $form['format_plural'] = array(
      '#type' => 'checkbox',
      '#title' => t('Format plural'),
      '#description' => t('If checked, special handling will be used for plurality.'),
      '#default_value' => $this->options['format_plural'],
    );
    $form['format_plural_singular'] = array(
      '#type' => 'textfield',
      '#title' => t('Singular form'),
      '#default_value' => $this->options['format_plural_singular'],
      '#description' => t('Text to use for the singular form.'),
      '#process' => array('ctools_dependent_process'),
      '#dependency' => array('edit-options-format-plural' => array(TRUE)),
    );
    $form['format_plural_plural'] = array(
      '#type' => 'textfield',
      '#title' => t('Plural form'),
      '#default_value' => $this->options['format_plural_plural'],
      '#description' => t('Text to use for the plural form, @count will be replaced with the value.'),
      '#process' => array('ctools_dependent_process'),
      '#dependency' => array('edit-options-format-plural' => array(TRUE)),
    );
    $form['prefix'] = array(
      '#type' => 'textfield',
      '#title' => t('Prefix'),
      '#default_value' => $this->options['prefix'],
      '#description' => t('Text to put before the number, such as currency symbol.'),
    );
    $form['suffix'] = array(
      '#type' => 'textfield',
      '#title' => t('Suffix'),
      '#default_value' => $this->options['suffix'],
      '#description' => t('Text to put after the number, such as currency symbol.'),
    );
  }

  function render($values) {
    $value = $values->{$this->field_alias};
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
    if (!empty($this->options['format_plural'])) {
      $value = format_plural($value, $this->options['format_plural_singular'], $this->options['format_plural_plural']);
    }

    return check_plain($this->options['prefix'] . $value . $this->options['suffix']);
  }
}
