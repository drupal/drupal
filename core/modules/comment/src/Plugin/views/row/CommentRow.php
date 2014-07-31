<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\views\row\CommentRow.
 */

namespace Drupal\comment\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\row\EntityRow;

/**
 * Plugin which performs a comment_view on the resulting object.
 *
 * @ViewsRow(
 *   id = "entity:comment",
 * )
 */
class CommentRow extends EntityRow {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['links'] = array('default' => TRUE);
    $options['view_mode']['default'] = 'full';
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['links'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display links'),
      '#default_value' => $this->options['links'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    $build = parent::render($row);
    if (!$this->options['links']) {
      unset($build['links']);
    }
    return $build;
  }

}
