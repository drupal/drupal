<?php

/**
 * @file
 * Definition of views_handler_field_comment_link_edit.
 */

/**
 * Field handler to present a link node edit.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_comment_link_edit extends views_handler_field_comment_link {
  function option_definition() {
    $options = parent::option_definition();
    $options['destination'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['destination'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use destination'),
      '#description' => t('Add destination to the link'),
      '#default_value' => $this->options['destination'],
      '#fieldset' => 'more',
    );
  }

  function render_link($data, $values) {
    parent::render_link($data, $values);
    // ensure user has access to edit this comment.
    $comment = $this->get_value($values);
    if (!comment_access('edit', $comment)) {
      return;
    }

    $text = !empty($this->options['text']) ? $this->options['text'] : t('edit');
    unset($this->options['alter']['fragment']);

    if (!empty($this->options['destination'])) {
      $this->options['alter']['query'] = drupal_get_destination();
    }

    $this->options['alter']['path'] = "comment/" . $comment->cid . "/edit";

    return $text;
  }
}
