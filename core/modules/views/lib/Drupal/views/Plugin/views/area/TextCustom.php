<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\area\TextCustom.
 */

namespace Drupal\views\Plugin\views\area;

use Drupal\Core\Annotation\Plugin;

/**
 * Views area text handler.
 *
 * @ingroup views_area_handlers
 *
 * @Plugin(
 *   id = "text_custom"
 * )
 */
class TextCustom extends AreaPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['content'] = array('default' => '', 'translatable' => TRUE);
    $options['tokenize'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['content'] = array(
      '#type' => 'textarea',
      '#default_value' => $this->options['content'],
      '#rows' => 6,
    );

    // Add tokenization form elements.
    $this->tokenForm($form, $form_state);
  }

  // Empty, so we don't inherit submitOptionsForm from the parent.
  public function submitOptionsForm(&$form, &$form_state) {
  }

  function render($empty = FALSE) {
    if (!$empty || !empty($this->options['empty'])) {
      return $this->render_textarea($this->options['content']);
    }

    return '';
  }

  /**
   * Render a text area with filter_xss_admin.
   */
  function render_textarea($value) {
    if ($value) {
      if ($this->options['tokenize']) {
        $value = $this->view->style_plugin->tokenize_value($value, 0);
      }
      return $this->sanitizeValue($value, 'xss_admin');
    }
  }

}
