<?php

/**
 * @file
 * Definition of views_handler_field_comment_node_link.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\views\Plugins\views\field\Entity;
use Drupal\Core\Annotation\Plugin;

/**
 * Handler for showing comment module's node link.
 *
 * @ingroup views_field_handlers
 */

/**
 * @Plugin(
 *   plugin_id = "comment_node_link"
 * )
 */
class NodeLink extends Entity {
  function construct() {
    parent::construct();

    // Add the node fields that comment_link will need..
    $this->additional_fields['nid'] = array(
      'field' => 'nid',
    );
    $this->additional_fields['type'] = array(
      'field' => 'type',
    );
    $this->additional_fields['comment'] = array(
      'field' => 'comment',
    );
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['teaser'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  function options_form(&$form, &$form_state) {
    $form['teaser'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show teaser-style link'),
      '#default_value' => $this->options['teaser'],
      '#description' => t('Show the comment link in the form used on standard node teasers, rather than the full node form.'),
    );

    parent::options_form($form, $form_state);
  }

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    // Build fake $node.
    $node = $this->get_value($values);

    // Call comment.module's hook_link: comment_link($type, $node = NULL, $teaser = FALSE)
    // Call node by reference so that something is changed here
    comment_node_view($node, $this->options['teaser'] ? 'teaser' : 'full');
    // question: should we run these through:    drupal_alter('link', $links, $node);
    // might this have unexpected consequences if these hooks expect items in $node that we don't have?

    // Only render the links, if they are defined.
    return !empty($node->content['links']['comment']) ? drupal_render($node->content['links']['comment']) : '';
  }
}
