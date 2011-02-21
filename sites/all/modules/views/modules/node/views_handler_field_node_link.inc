<?php
// $Id: views_handler_field_node_link.inc,v 1.1.6.2 2010/11/30 20:36:01 merlinofchaos Exp $
/**
 * Field handler to present a link to the node.
 */
class views_handler_field_node_link extends views_handler_field {
  function construct() {
    parent::construct();
    $this->additional_fields['nid'] = 'nid';
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['text'] = array('default' => '', 'translatable' => TRUE);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Text to display'),
      '#default_value' => $this->options['text'],
    );
  }

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    return $this->render_link(check_plain($values->{$this->aliases['nid']}), $values);
  }

  function render_link($data, $values) {
    $nid = $values->{$this->aliases['nid']};
    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "node/$nid";
    $text = !empty($this->options['text']) ? $this->options['text'] : t('view');
    return $text;
  }
}
