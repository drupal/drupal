<?php
// $Id: views_handler_field_node.inc,v 1.7.4.8 2010/11/18 23:32:04 merlinofchaos Exp $
/**
 * @file
 * Contains the basic 'node' field handler.
 */

/**
 * Field handler to provide simple renderer that allows linking to a node.
 */
class views_handler_field_node extends views_handler_field {

  function init(&$view, &$options) {
    parent::init($view, $options);
    if (!empty($this->options['link_to_node'])) {
      $this->additional_fields['nid'] = array('table' => 'node', 'field' => 'nid');
      if (module_exists('translation')) {
        $this->additional_fields['language'] = array('table' => 'node', 'field' => 'language');
      }
    }
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['link_to_node'] = array('default' => TRUE);
    return $options;
  }

  /**
   * Provide link to node option
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['link_to_node'] = array(
      '#title' => t('Link this field to its node'),
      '#description' => t('This will override any other link you have set.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_node']),
    );
  }

  /**
   * Render whatever the data is as a link to the node.
   *
   * Data should be made XSS safe prior to calling this function.
   */
  function render_link($data, $values) {
    if (!empty($this->options['link_to_node'])) {
      if ($data !== NULL && $data !== '') {
        $this->options['alter']['make_link'] = TRUE;
        $this->options['alter']['path'] = "node/" . $values->{$this->aliases['nid']};
        if (isset($this->aliases['language'])) {
          $languages = language_list();
          if (isset($languages[$values->{$this->aliases['language']}])) {
            $this->options['alter']['language'] = $languages[$values->{$this->aliases['language']}];
          }
          else {
            unset($this->options['alter']['language']);
          }
        }
      }
      else {
        $this->options['alter']['make_link'] = FALSE;
      } 
    }
    return $data;
  }

  function render($values) {
    return $this->render_link(check_plain($values->{$this->field_alias}), $values);
  }
}
