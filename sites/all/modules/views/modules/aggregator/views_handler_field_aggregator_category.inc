<?php
// $Id: views_handler_field_aggregator_category.inc,v 1.1.6.1 2009/11/02 22:01:26 merlinofchaos Exp $

/**
 * Field handler to provide simple renderer that allows linking to aggregator
 * category.
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
    $options['link_to_category'] = array('default' => FALSE);
    return $options;
  }

  /**
   * Provide link to category option
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['link_to_category'] = array(
      '#title' => t('Link this field to its aggregator category page'),
      '#description' => t('This will override any other link you have set.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_category']),
    );
  }

  /**
   * Render whatever the data is as a link to the category.
   *
   * Data should be made XSS safe prior to calling this function.
   */
  function render_link($data, $values) {
    if (!empty($this->options['link_to_category']) && !empty($values->{$this->aliases['cid']}) && $data !== NULL && $data !== '') {
      $cid = $values->{$this->aliases['cid']};
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = "aggregator/category/$cid";
    }
    return $data;
  }

  function render($values) {
    return $this->render_link(check_plain($values->{$this->field_alias}), $values);
  }
}
