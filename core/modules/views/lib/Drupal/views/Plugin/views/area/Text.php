<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\area\Text.
 */

namespace Drupal\views\Plugin\views\area;

use Drupal\Component\Annotation\PluginID;

/**
 * Views area text handler.
 *
 * @ingroup views_area_handlers
 *
 * @PluginID("text")
 */
class Text extends TokenizeAreaPluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\area\TokenizeAreaPluginBase::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['content'] = array('default' => '', 'translatable' => TRUE, 'format_key' => 'format');
    $options['format'] = array('default' => NULL);
    return $options;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\area\TokenizeAreaPluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['content'] = array(
      '#title' => t('Content'),
      '#type' => 'text_format',
      '#default_value' => $this->options['content'],
      '#rows' => 6,
      '#format' => isset($this->options['format']) ? $this->options['format'] : filter_default_format(),
      '#editor' => FALSE,
    );
  }

  public function submitOptionsForm(&$form, &$form_state) {
    $form_state['values']['options']['format'] = $form_state['values']['options']['content']['format'];
    $form_state['values']['options']['content'] = $form_state['values']['options']['content']['value'];
    parent::submitOptionsForm($form, $form_state);
  }

  /**
   * Implements \Drupal\views\Plugin\views\area\AreaPluginBase::render().
   */
  public function render($empty = FALSE) {
    $format = isset($this->options['format']) ? $this->options['format'] : filter_default_format();
    if (!$empty || !empty($this->options['empty'])) {
      return array(
        '#markup' => $this->renderTextarea($this->options['content'], $format),
      );
    }

    return array();
  }

  /**
   * Render a text area, using the proper format.
   */
  public function renderTextarea($value, $format) {
    if ($value) {
      return check_markup($this->tokenizeValue($value), $format, '', FALSE);
    }
  }

}
