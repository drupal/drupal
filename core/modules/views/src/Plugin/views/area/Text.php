<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\area\Text.
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
    $options['content'] = array('default' => '', 'format_key' => 'format');
    $options['format'] = array('default' => NULL);
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
      '#default_value' => $this->options['content'],
      '#rows' => 6,
      '#format' => isset($this->options['format']) ? $this->options['format'] : filter_default_format(),
      '#editor' => FALSE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    $content = $form_state->getValue(array('options', 'content'));
    $form_state->setValue(array('options', 'format'), $content['format']);
    $form_state->setValue(array('options', 'content'), $content['value']);
    parent::submitOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    $format = isset($this->options['format']) ? $this->options['format'] : filter_default_format();
    if (!$empty || !empty($this->options['empty'])) {
      return array(
        '#type' => 'processed_text',
        '#text' => $this->tokenizeValue($this->options['content']),
        '#format' => $format,
      );
    }

    return array();
  }

}
