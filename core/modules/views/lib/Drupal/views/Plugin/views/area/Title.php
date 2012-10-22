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
      '#description' => t('Override the title of this view when it is empty.'),
    );

  }

  /**
   * Overrides Drupal\views\Plugin\views\AreaPluginBase::render().
   */
  function render($empty = FALSE) {
    if (!empty($this->options['title'])) {
      $this->view->setTitle(filter_xss_admin($this->options['title']), PASS_THROUGH);
    }

    return '';
  }

}
