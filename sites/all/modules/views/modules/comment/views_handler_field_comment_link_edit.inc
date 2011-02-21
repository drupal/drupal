<?php
// $Id: views_handler_field_comment_link_edit.inc,v 1.1.6.2 2010/11/18 07:29:03 dereine Exp $
/**
 * Field handler to present a link node edit.
 */
class views_handler_field_comment_link_edit extends views_handler_field_comment_link {
  function construct() {
    parent::construct();
    $this->additional_fields['uid'] = 'uid';
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['destination'] = array('default' => FALSE);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['destination'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use destination'),
      '#description' => t('Add destination to the link'),
      '#default_value' => $this->options['destination'],
    );
  }

  function render_link($data, $values) {
    parent::render_link($data, $values);
    // ensure user has access to edit this comment.
    $comment = new stdClass();
    $comment->cid = $values->{$this->aliases['cid']};
    $comment->uid = $values->{$this->aliases['uid']};
    if (!comment_access('edit', $comment)) {
      return;
    }

    $text = !empty($this->options['text']) ? $this->options['text'] : t('edit');
    unset($this->options['alter']['fragment']);

    if (!empty($this->options['destination'])) {
      $this->options['alter']['query'] = drupal_get_destination();
    }

    return $text;
  }
}

