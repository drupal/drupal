<?php
// $Id: views_handler_field_aggregator_title_link.inc,v 1.1.6.1 2010/11/18 07:29:03 dereine Exp $

 /**
 * Field handler that turns an item's title into a clickable link to the original
 * source article.
 */
class views_handler_field_aggregator_title_link extends views_handler_field {
  function construct() {
    parent::construct();
    $this->additional_fields['link'] = 'link';
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
    $value = $values->{$this->field_alias};
    $link = $values->{$this->aliases['link']};
    if (!empty($this->options['display_as_link'])) {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = $link;
      $this->options['alter']['html'] = TRUE;
    }

    return $data;
  }
}