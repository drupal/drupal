<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\area\TextCustom.
 */

namespace Drupal\views\Plugin\views\area;

use Drupal\Component\Annotation\PluginID;

/**
 * Views area text handler.
 *
 * @ingroup views_area_handlers
 *
 * @PluginID("text_custom")
 */
class TextCustom extends TokenizeAreaPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['content'] = array('default' => '', 'translatable' => TRUE);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['content'] = array(
      '#type' => 'textarea',
      '#default_value' => $this->options['content'],
      '#rows' => 6,
    );
  }

  /**
   * Implements \Drupal\views\Plugin\views\area\AreaPluginBase::render().
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
   * Render a text area with filter_xss_admin.
   */
  public function renderTextarea($value) {
    if ($value) {
      return $this->sanitizeValue($this->tokenizeValue($value), 'xss_admin');
    }
  }

}
