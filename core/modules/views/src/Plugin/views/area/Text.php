<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\area\Text.
 */

namespace Drupal\views\Plugin\views\area;

use Drupal\Core\Form\FormStateInterface;

/**
 * Views area text handler.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("text")
 */
class Text extends TokenizeAreaPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['content'] = array(
      'contains' => array(
        'value' => array('default' => ''),
        'format' => array('default' => NULL),
      ),
    );
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['content'] = array(
      '#title' => $this->t('Content'),
      '#type' => 'text_format',
      '#default_value' => $this->options['content']['value'],
      '#rows' => 6,
      '#format' => isset($this->options['content']['format']) ? $this->options['content']['format'] : filter_default_format(),
      '#editor' => FALSE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    $format = isset($this->options['content']['format']) ? $this->options['content']['format'] : filter_default_format();
    if (!$empty || !empty($this->options['empty'])) {
      return array(
        '#type' => 'processed_text',
        '#text' => $this->tokenizeValue($this->options['content']['value']),
        '#format' => $format,
      );
    }

    return array();
  }

}
