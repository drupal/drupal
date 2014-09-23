<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\argument_default\Fixed.
 */

namespace Drupal\views\Plugin\views\argument_default;

use Drupal\Core\Form\FormStateInterface;

/**
 * The fixed argument default handler.
 *
 * @ingroup views_argument_default_plugins
 *
 * @ViewsArgumentDefault(
 *   id = "fixed",
 *   title = @Translation("Fixed")
 * )
 */
class Fixed extends ArgumentDefaultPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['argument'] = array('default' => '');

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['argument'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Fixed value'),
      '#default_value' => $this->options['argument'],
    );
  }

  /**
   * Return the default argument.
   */
  public function getArgument() {
    return $this->options['argument'];
  }

}
