<?php

/**
 * @file
 * Definition of views_handler_field_node_language.
 */

/**
 * Field handler to translate a language into its readable form.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_node_language extends views_handler_field_node {
  function option_definition() {
    $options = parent::option_definition();
    $options['native_language'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['native_language'] = array(
      '#title' => t('Native language'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['native_language'],
      '#description' => t('If enabled, the native name of the language will be displayed'),
    );
  }

  function render($values) {
    $languages = views_language_list(empty($this->options['native_language']) ? 'name' : 'native');
    $value = $this->get_value($values);
    $value = isset($languages[$value]) ? $languages[$value] : '';
    return $this->render_link($value, $values);
  }
}
