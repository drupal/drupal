<?php

/**
 * @file
 * Definition of views_handler_field_comment.
 */

/**
 * Field handler to allow linking to a comment.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_comment extends views_handler_field {
  /**
   * Override init function to provide generic option to link to comment.
   */
  function init(&$view, &$options) {
    parent::init($view, $options);
    if (!empty($this->options['link_to_comment'])) {
      $this->additional_fields['cid'] = 'cid';
      $this->additional_fields['nid'] = 'nid';
    }
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['link_to_comment'] = array('default' => TRUE, 'bool' => TRUE);
    $options['link_to_node'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  /**
   * Provide link-to-comment option
   */
  function options_form(&$form, &$form_state) {
    $form['link_to_comment'] = array(
      '#title' => t('Link this field to its comment'),
      '#description' => t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_comment'],
    );
    $form['link_to_node'] = array(
      '#title' => t('Link field to the node if there is no comment.'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_node'],
    );
    parent::options_form($form, $form_state);
  }

  function render_link($data, $values) {
    if (!empty($this->options['link_to_comment'])) {
      $this->options['alter']['make_link'] = TRUE;
      $nid = $this->get_value($values, 'nid');
      $cid = $this->get_value($values, 'cid');
      if (!empty($cid)) {
        $this->options['alter']['path'] = "comment/" . $cid;
        $this->options['alter']['fragment'] = "comment-" . $cid;
      }
      // If there is no comment link to the node.
      else if ($this->options['link_to_node']) {
        $this->options['alter']['path'] = "node/" . $nid;
      }
    }

    return $data;
  }

  function render($values) {
    $value = $this->get_value($values);
    return $this->render_link($this->sanitize_value($value), $values);
  }
}
