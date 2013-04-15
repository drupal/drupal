<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\area\Title.
 */

namespace Drupal\views\Plugin\views\area;

use Drupal\Component\Annotation\PluginID;

/**
 * Views area title override handler.
 *
 * @ingroup views_area_handlers
 *
 * @PluginID("title")
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
   * Overrides Drupal\views\Plugin\views\AreaPluginBase::preRender().
   */
  public function preRender(array $results) {
    parent::preRender($results);

    // If a title is provided, process it.
    if (!empty($this->options['title'])) {
      $value = $this->globalTokenReplace($this->options['title']);
      $this->view->setTitle($this->sanitizeValue($value, 'xss_admin'), PASS_THROUGH);
    }
  }

  /**
   * Implements \Drupal\views\Plugin\views\area\AreaPluginBase::render().
   */
  public function render($empty = FALSE) {
    // Do nothing for this handler by returning an empty render array.
    return array();
  }

}
