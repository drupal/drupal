<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\field\Link.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Component\Annotation\PluginID;

/**
 * Base field handler to present a link.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("comment_link")
 */
class Link extends FieldPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['text'] = array('default' => '', 'translatable' => TRUE);
    $options['link_to_node'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
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
    parent::buildOptionsForm($form, $form_state);
  }

  public function query() {}

  public function render($values) {
    $comment = $this->getEntity($values);
    return $this->render_link($comment, $values);
  }

  function render_link($data, $values) {
    $text = !empty($this->options['text']) ? $this->options['text'] : t('view');
    $comment = $data;
    $nid = $comment->nid;
    $cid = $comment->id();

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['html'] = TRUE;

    if (!empty($cid)) {
      $this->options['alter']['path'] = "comment/" . $cid;
      $this->options['alter']['fragment'] = "comment-" . $cid;
    }
    // If there is no comment link to the node.
    elseif ($this->options['link_to_node']) {
      $this->options['alter']['path'] = "node/" . $nid;
    }

    return $text;
  }

}
