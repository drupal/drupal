<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\field\NodeLink.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\ResultRow;

/**
 * Handler for showing comment module's node link.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("comment_node_link")
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

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $node = $this->getEntity($values);
    comment_node_view($node, $this->options['teaser'] ? 'teaser' : 'full');

    // Only render the links if they are defined.
    return !empty($node->content['links']['comment']) ? drupal_render($node->content['links']['comment']) : '';
  }

}
