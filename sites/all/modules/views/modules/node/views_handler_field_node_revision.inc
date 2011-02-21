<?php
// $Id: views_handler_field_node_revision.inc,v 1.1.6.3 2010/08/26 09:41:55 dereine Exp $
/**
 * @file
 * Contains the basic 'node_revision' field handler.
 */

class views_handler_field_node_revision extends views_handler_field_node {
  function init(&$view, &$options) {
    parent::init($view, $options);
    if (!empty($this->options['link_to_node_revision'])) {
      $this->additional_fields['vid'] = 'vid';
      $this->additional_fields['nid'] = 'nid';
      if (module_exists('translation')) {
        $this->additional_fields['language'] = array('table' => 'node', 'field' => 'language');
      }
    }
  }
  function option_definition() {
    $options = parent::option_definition();
    $options['link_to_node_revision'] = array('default' => FALSE);
    return $options;
  }

  /**
   * Provide link to revision option.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['link_to_node_revision'] = array(
      '#title' => t('Link this field to its node revision'),
      '#description' => t('This will override any other link you have set.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_node_revision']),
    );
  }

  /**
   * Render whatever the data is as a link to the node.
   *
   * Data should be made XSS safe prior to calling this function.
   */
  function render_link($data, $values) {
    if (!empty($this->options['link_to_node_revision']) && $data !== NULL && $data !== '') {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = "node/" . $values->{$this->aliases['nid']} . '/revisions/' . $values->{$this->aliases['vid']} .'/view';
      if (isset($this->aliases['language'])) {
        $languages = language_list();
        if (isset($languages[$values->{$this->aliases['language']}])) {
          $this->options['alter']['language'] = $languages[$values->{$this->aliases['language']}];
        }
      }
    }
    else {
      return parent::render_link($data, $values);
    }
    return $data;
  }
}

