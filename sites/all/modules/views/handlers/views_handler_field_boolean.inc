<?php
// $Id: views_handler_field_boolean.inc,v 1.3.4.3 2010/08/26 09:41:55 dereine Exp $

/**
 * A handler to provide proper displays for booleans.
 *
 * Allows for display of true/false, yes/no, on/off.
 *
 * Definition terms:
 *   - output formats: An array where the first entry is displayed on boolean false
 *      and the second is displayed on boolean true. An example for sticky is:
 *      @code
 *      'output formats' => array(
 *        'sticky' => array('', t('Sticky')),
 *      ),
 *      @endcode
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_boolean extends views_handler_field {
  function option_definition() {
    $options = parent::option_definition();
    $options['type'] = array('default' => 'yes-no');
    $options['not'] = array('definition bool' => 'reverse');

    return $options;
  }

  function init(&$view, &$options) {
    parent::init($view, $options);

    $default_formats = array(
      'yes-no' => array(t('Yes'), t('No')),
      'true-false' => array(t('True'), t('False')),
      'on-off' => array(t('On'), t('Off')),
    );
    $output_formats = isset($this->definition['output formats']) ? $this->definition['output formats'] : array();
    $this->formats = array_merge($default_formats, $output_formats);
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    foreach ($this->formats as $key => $item) {
      $options[$key] = implode('/', $item);
    }

    $form['type'] = array(
      '#type' => 'select',
      '#title' => t('Output format'),
      '#options' => $options,
      '#default_value' => $this->options['type'],
    );
    $form['not'] = array(
      '#type' => 'checkbox',
      '#title' => t('Reverse'),
      '#description' => t('If checked, true will be displayed as false.'),
      '#default_value' => $this->options['not'],
    );
  }

  function render($values) {
    $value = $values->{$this->field_alias};
    if (!empty($this->options['not'])) {
      $value = !$value;
    }

    if (isset($this->formats[$this->options['type']])) {
      return $value ? $this->formats[$this->options['type']][0] : $this->formats[$this->options['type']][1];
    }
    else {
      return $value ? $this->formats['yes-no'][0] : $this->formats['yes-no'][1];
    }
  }
}
