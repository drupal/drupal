<?php

/**
 * @file
 * Definition of views_handler_field_custom.
 */

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_custom extends views_handler_field {
  function query() {
    // do nothing -- to override the parent query.
  }

  function option_definition() {
    $options = parent::option_definition();

    // Override the alter text option to always alter the text.
    $options['alter']['contains']['alter_text'] = array('default' => TRUE, 'bool' => TRUE);
    $options['hide_alter_empty'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    // Remove the checkbox
    unset($form['alter']['alter_text']);
    unset($form['alter']['text']['#dependency']);
    unset($form['alter']['text']['#process']);
    unset($form['alter']['help']['#dependency']);
    unset($form['alter']['help']['#process']);
    $form['#pre_render'][] = 'views_handler_field_custom_pre_render_move_text';
  }

  function render($values) {
    // Return the text, so the code never thinks the value is empty.
    return $this->options['alter']['text'];
  }
}

/**
 * Prerender function to move the textarea to the top.
 */
function views_handler_field_custom_pre_render_move_text($form) {
  $form['text'] = $form['alter']['text'];
  $form['help'] = $form['alter']['help'];
  unset($form['alter']['text']);
  unset($form['alter']['help']);

  return $form;
}
