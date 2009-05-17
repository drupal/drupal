<?php

/**
 * @file
 * Definition of views_handler_field_aggregator_title_link.
 */

/**
 * Field handler that turns an item's title into a clickable link to the original
 * source article.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_aggregator_title_link extends views_handler_field {
  function construct() {
    parent::construct();
    $this->additional_fields['link'] = 'link';
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['display_as_link'] = array('default' => TRUE, 'bool' => TRUE);

    return $options;
  }

  /**
   * Provide link to the page being visited.
   */
  function options_form(&$form, &$form_state) {
    $form['display_as_link'] = array(
      '#title' => t('Display as link'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['display_as_link']),
    );
    parent::options_form($form, $form_state);
  }

  function render($values) {
    $value = $this->get_value($values);
    return $this->render_link($this->sanitize_value($value), $values);
  }

  function render_link($data, $values) {
    $link = $this->get_value($values, 'link');
    if (!empty($this->options['display_as_link'])) {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = $link;
      $this->options['alter']['html'] = TRUE;
    }

    return $data;
  }
}
