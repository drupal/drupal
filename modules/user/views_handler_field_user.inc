<?php

/**
 * @file
 * Definition of views_handler_field_user.
 */

/**
 * Field handler to provide simple renderer that allows linking to a user.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_user extends views_handler_field {
  /**
   * Override init function to provide generic option to link to user.
   */
  function init(&$view, &$data) {
    parent::init($view, $data);
    if (!empty($this->options['link_to_user'])) {
      $this->additional_fields['uid'] = 'uid';
    }
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['link_to_user'] = array('default' => TRUE, 'bool' => TRUE);
    return $options;
  }

  /**
   * Provide link to node option
   */
  function options_form(&$form, &$form_state) {
    $form['link_to_user'] = array(
      '#title' => t('Link this field to its user'),
      '#description' => t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_user'],
    );
    parent::options_form($form, $form_state);
  }

  function render_link($data, $values) {
    if (!empty($this->options['link_to_user']) && user_access('access user profiles') && ($uid = $this->get_value($values, 'uid')) && $data !== NULL && $data !== '') {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = "user/" . $uid;
    }
    return $data;
  }

  function render($values) {
    $value = $this->get_value($values);
    return $this->render_link($this->sanitize_value($value), $values);
  }
}
