<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\area\TextCustom.
 */

namespace Drupal\views\Plugin\views\area;

use Drupal\Core\Form\FormStateInterface;

/**
 * Views area text handler.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("text_custom")
 */
class TextCustom extends TokenizeAreaPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['content'] = array('default' => '');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['content'] = array(
      '#title' => $this->t('Content'),
      '#type' => 'textarea',
      '#default_value' => $this->options['content'],
      '#rows' => 6,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if (!$empty || !empty($this->options['empty'])) {
      return array(
        '#markup' => $this->renderTextarea($this->options['content']),
      );
    }

    return array();
  }

  /**
   * Render a text area with \Drupal\Component\Utility\Xss::filterAdmin().
   */
  public function renderTextarea($value) {
    if ($value) {
      return $this->sanitizeValue($this->tokenizeValue($value), 'xss_admin');
    }
  }

}
