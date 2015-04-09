<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\views\field\LinkEdit.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to edit a comment.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("comment_link_edit")
 */
class LinkEdit extends Link {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['destination'] = array('default' => FALSE);

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['destination'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use destination'),
      '#description' => $this->t('Add destination to the link'),
      '#default_value' => $this->options['destination'],
    );
  }

  /**
   * Prepare the link for editing the comment.
   *
   * @param \Drupal\Core\Entity\EntityInterface $data
   *   The comment entity.
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from a single row of a view's query result.
   *
   * @return string
   *   Returns a string for the link text.
   */
  protected function renderLink($data, ResultRow $values) {
    parent::renderLink($data, $values);
    // Ensure user has access to edit this comment.
    $comment = $this->getValue($values);
    if (!$comment->access('update')) {
      return;
    }

    $text = !empty($this->options['text']) ? $this->options['text'] : $this->t('Edit');
    unset($this->options['alter']['fragment']);

    if (!empty($this->options['destination'])) {
      $this->options['alter']['query'] = $this->getDestinationArray();
    }

    $this->options['alter']['url'] = $comment->urlInfo('edit-form');

    return $text;
  }

}
