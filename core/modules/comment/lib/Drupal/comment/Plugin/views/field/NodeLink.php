<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\field\NodeLink.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Handler for showing comment module's node link.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "comment_node_link",
 *   module = "comment"
 * )
 */
class NodeLink extends FieldPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['teaser'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    $form['teaser'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show teaser-style link'),
      '#default_value' => $this->options['teaser'],
      '#description' => t('Show the comment link in the form used on standard node teasers, rather than the full node form.'),
    );

    parent::buildOptionsForm($form, $form_state);
  }

  public function query() {}

  function render($values) {
    $node = $this->get_entity($values);

    // Call comment.module's hook_link: comment_link($type, $node = NULL, $teaser = FALSE)
    // Call node by reference so that something is changed here
    comment_node_view($node, $this->options['teaser'] ? 'teaser' : 'full');
    // question: should we run these through:    drupal_alter('link', $links, $node);
    // might this have unexpected consequences if these hooks expect items in $node that we don't have?

    // Only render the links, if they are defined.
    return !empty($node->content['links']['comment']) ? drupal_render($node->content['links']['comment']) : '';
  }

}
