<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\area\TextCustom.
 */

namespace Drupal\views\Plugin\views\area;

use Drupal\Component\Annotation\Plugin;

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

  /**
   * Implements \Drupal\views\Plugin\views\area\AreaPluginBase::render().
   */
  function render($empty = FALSE) {
    if (!$empty || !empty($this->options['empty'])) {
      return array(
        '#markup' => $this->render_textarea($this->options['content']),
      );
    }

    return array();
  }

  /**
   * Render a text area with filter_xss_admin.
   */
  function render_textarea($value) {
    if ($value) {
      if ($this->options['tokenize']) {
        $value = $this->view->style_plugin->tokenize_value($value, 0);
      }
      return $this->sanitizeValue($this->globalTokenReplace($value), 'xss_admin');
    }
  }

}
