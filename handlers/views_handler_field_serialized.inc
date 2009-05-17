<?php

/**
 * @file
 * Definition of views_handler_field_serialized.
 */

/**
 * Field handler to show data of serialized fields.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_serialized extends views_handler_field {

  function option_definition() {
    $options = parent::option_definition();
    $options['format'] = array('default' => 'unserialized');
    $options['key'] = array('default' => '');
    return $options;
  }


  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['format'] = array(
      '#type' => 'select',
      '#title' => t('Display format'),
      '#description' => t('How should the serialized data be displayed. You can choose a custom array/object key or a print_r on the full output.'),
      '#options' => array(
        'unserialized' => t('Full data (unserialized)'),
        'serialized' => t('Full data (serialized)'),
        'key' => t('A certain key'),
      ),
      '#default_value' => $this->options['format'],
    );
    $form['key'] = array(
      '#type' => 'textfield',
      '#title' => t('Which key should be displayed'),
      '#default_value' => $this->options['key'],
      '#dependency' => array('edit-options-format' => array('key')),
    );
  }

  function options_validate(&$form, &$form_state) {
    // Require a key if the format is key.
    if ($form_state['values']['options']['format'] == 'key' && $form_state['values']['options']['key'] == '') {
      form_error($form['key'], t('You have to enter a key if you want to display a key of the data.'));
    }
  }

  function render($values) {
    $value = $values->{$this->field_alias};

    if ($this->options['format'] == 'unserialized') {
      return check_plain(print_r(unserialize($value), TRUE));
    }
    elseif ($this->options['format'] == 'key' && !empty($this->options['key'])) {
      $value = (array) unserialize($value);
      return check_plain($value[$this->options['key']]);
    }

    return $value;
  }
}
