<?php

/**
 * @file
 * Definition of views_handler_field_aggregator_category.
 */

/**
 * Field handler to provide simple renderer that allows linking to aggregator
 * category.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_aggregator_category extends views_handler_field {
  /**
   * Constructor to provide additional field to add.
   */
  function construct() {
    parent::construct();
    $this->additional_fields['cid'] = 'cid';
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['link_to_category'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  /**
   * Provide link to category option
   */
  function options_form(&$form, &$form_state) {
    $form['link_to_category'] = array(
      '#title' => t('Link this field to its aggregator category page'),
      '#description' => t('This will override any other link you have set.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_category']),
    );
    parent::options_form($form, $form_state);
  }

  /**
   * Render whatever the data is as a link to the category.
   *
   * Data should be made XSS safe prior to calling this function.
   */
  function render_link($data, $values) {
    $cid = $this->get_value($values, 'cid');
    if (!empty($this->options['link_to_category']) && !empty($cid) && $data !== NULL && $data !== '') {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = "aggregator/category/$cid";
    }
    return $data;
  }

  function render($values) {
    $value = $this->get_value($values);
    return $this->render_link($this->sanitize_value($value), $values);
  }
}
