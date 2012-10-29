<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\area\Text.
 */

namespace Drupal\views\Plugin\views\area;

use Drupal\Core\Annotation\Plugin;

/**
 * Views area text handler.
 *
 * @ingroup views_area_handlers
 *
 * @Plugin(
 *   id = "text"
 * )
 */
class Text extends AreaPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['content'] = array('default' => '', 'translatable' => TRUE, 'format_key' => 'format');
    $options['format'] = array('default' => NULL);
    $options['tokenize'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['content'] = array(
      '#type' => 'text_format',
      '#default_value' => $this->options['content'],
      '#rows' => 6,
      '#format' => isset($this->options['format']) ? $this->options['format'] : filter_default_format(),
      '#wysiwyg' => FALSE,
    );

    // Add tokenization form elements.
    $this->tokenForm($form, $form_state);
  }

  public function submitOptionsForm(&$form, &$form_state) {
    $form_state['values']['options']['format'] = $form_state['values']['options']['content']['format'];
    $form_state['values']['options']['content'] = $form_state['values']['options']['content']['value'];
    parent::submitOptionsForm($form, $form_state);
  }

  function render($empty = FALSE) {
    $format = isset($this->options['format']) ? $this->options['format'] : filter_default_format();
    if (!$empty || !empty($this->options['empty'])) {
      return $this->render_textarea($this->options['content'], $format);
    }
    return '';
  }

  /**
   * Render a text area, using the proper format.
   */
  function render_textarea($value, $format) {
    if ($value) {
      if ($this->options['tokenize']) {
        $value = $this->view->style_plugin->tokenize_value($value, 0);
      }
      return check_markup($value, $format, '', FALSE);
    }
  }

}
