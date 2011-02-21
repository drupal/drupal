<?php
// $Id: views_handler_field_accesslog_path.inc,v 1.1.6.1 2010/11/18 07:29:03 dereine Exp $
/**
 * Field handler to provide simple renderer that turns a URL into a clickable link.
 */
class views_handler_field_accesslog_path extends views_handler_field {
  /**
   * Override init function to provide generic option to link to node.
   */
  function init(&$view, &$data) {
    parent::init($view, $data);
    if (isset($data['display_as_link'])) {
      $this->additional_fields[] = 'path';
    }
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['display_as_link'] = array('default' => TRUE);

    return $options;
  }

  /**
   * Provide link to the page being visited.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['display_as_link'] = array(
      '#title' => t('Display as link'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['display_as_link']),
    );
  }

  function render($values) {
    return $this->render_link(check_plain($values->{$this->field_alias}), $values);
  }

  function render_link($data, $values) {
    if (!empty($this->options['display_as_link'])) {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = $values->{$this->aliases['path']};
      $this->options['alter']['html'] = TRUE;
    }

    return $data;
  }
}
