<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\field\Custom.
 */

namespace Drupal\views\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("custom")
 */
class Custom extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Override the alter text option to always alter the text.
    $options['alter']['contains']['alter_text'] = array('default' => TRUE);
    $options['hide_alter_empty'] = array('default' => FALSE);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Remove the checkbox
    unset($form['alter']['alter_text']);
    unset($form['alter']['text']['#states']);
    unset($form['alter']['help']['#states']);
    $form['#pre_render'][] = array($this, 'preRenderCustomForm');
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // Return the text, so the code never thinks the value is empty.
    return $this->options['alter']['text'];
  }

  /**
   * Prerender function to move the textarea to the top of a form.
   *
   * @param array $form
   *   The form build array.
   *
   * @return array
   *   The modified form build array.
   */
  public function preRenderCustomForm($form) {
    $form['text'] = $form['alter']['text'];
    $form['help'] = $form['alter']['help'];
    unset($form['alter']['text']);
    unset($form['alter']['help']);

    return $form;
  }

}
