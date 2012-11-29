<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\area\Title.
 */

namespace Drupal\views\Plugin\views\area;

use Drupal\Core\Annotation\Plugin;

/**
 * Views area title override handler.
 *
 * @ingroup views_area_handlers
 *
 * @Plugin(
 *   id = "title"
 * )
 */
class Title extends AreaPluginBase {

  /**
   * Overrides Drupal\views\Plugin\views\AreaPluginBase::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['title'] = array('default' => '', 'translatable' => TRUE);
    return $options;
  }

  /**
   * Overrides Drupal\views\Plugin\views\AreaPluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Overridden title'),
      '#default_value' => $this->options['title'],
      '#description' => t('Override the title of this view when it is empty. The available global tokens below can be used here.'),
    );

    // Don't use the AreaPluginBase tokenForm method, we don't want row tokens.
    $this->globalTokenForm($form, $form_state);
  }

  /**
   * Overrides Drupal\views\Plugin\views\AreaPluginBase::render().
   */
  function render($empty = FALSE) {
    if (!empty($this->options['title'])) {
      $value = $this->globalTokenReplace($this->options['title']);
      $this->view->setTitle($this->sanitizeValue($value, 'xss_admin'), PASS_THROUGH);
    }

    return '';
  }

}
