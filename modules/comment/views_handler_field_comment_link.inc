<?php

/**
 * @file
 * Definition of views_handler_field_comment_link.
 */

/**
 * Base field handler to present a link.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_comment_link extends views_handler_field_entity {
  function construct() {
    parent::construct();
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['text'] = array('default' => '', 'translatable' => TRUE);
    $options['link_to_node'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  function options_form(&$form, &$form_state) {
    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Text to display'),
      '#default_value' => $this->options['text'],
    );
    $form['link_to_node'] = array(
      '#title' => t('Link field to the node if there is no comment.'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_node'],
    );
    parent::options_form($form, $form_state);
  }

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    $value = $this->get_value($values, 'cid');
    return $this->render_link($this->sanitize_value($value), $values);
  }

  function render_link($data, $values) {
    $text = !empty($this->options['text']) ? $this->options['text'] : t('view');
    $comment = $this->get_value($values);
    $nid = $comment->nid;
    $cid = $comment->cid;

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['html'] = TRUE;

    if (!empty($cid)) {
      $this->options['alter']['path'] = "comment/" . $cid;
      $this->options['alter']['fragment'] = "comment-" . $cid;
    }
    // If there is no comment link to the node.
    else if ($this->options['link_to_node']) {
      $this->options['alter']['path'] = "node/" . $nid;
    }

    return $text;
  }
}
