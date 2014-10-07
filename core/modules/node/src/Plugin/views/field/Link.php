<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\field\Link.
 */

namespace Drupal\node\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to the node.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_link")
 */
class Link extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['text'] = array('default' => '');
    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Text to display'),
      '#default_value' => $this->options['text'],
    );
    parent::buildOptionsForm($form, $form_state);

    // The path is set by renderLink function so don't allow to set it.
    $form['alter']['path'] = array('#access' => FALSE);
    $form['alter']['external'] = array('#access' => FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if ($entity = $this->getEntity($values)) {
      return $this->renderLink($entity, $values);
    }
  }

  /**
   * Prepares the link to the node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node entity this field belongs to.
   * @param ResultRow $values
   *   The values retrieved from the view's result set.
   *
   * @return string
   *   Returns a string for the link text.
   */
  protected function renderLink($node, ResultRow $values) {
    if ($node->access('view')) {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = 'node/' . $node->id();
      $text = !empty($this->options['text']) ? $this->options['text'] : $this->t('View');
      return $text;
    }
  }

}
