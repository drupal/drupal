<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\argument_validator\Php.
 */

namespace Drupal\views\Plugin\views\argument_validator;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provide PHP code to validate whether or not an argument is ok.
 *
 * @ingroup views_argument_validate_plugins
 *
 * @Plugin(
 *   id = "php",
 *   title = @Translation("PHP Code")
 * )
 */
class Php extends ArgumentValidatorPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['code'] = array('default' => '');

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['code'] = array(
      '#type' => 'textarea',
      '#title' => t('PHP validate code'),
      '#default_value' => $this->options['code'],
      '#description' => t('Enter PHP code that returns TRUE or FALSE. No return is the same as FALSE, so be SURE to return something if you do not want to declare the argument invalid. Do not use &lt;?php ?&gt;. The argument to validate will be "$argument" and the view will be "$view". You may change the argument by setting "$handler->argument". You may change the title used for substitutions for this argument by setting "$handler->validated_title".'),
    );

    $this->checkAccess($form, 'code');
  }

  /**
   * Only let users with PHP block visibility permissions set/modify this
   * validate plugin.
   */
  public function access() {
    return user_access('use PHP for settings');
  }

  function validate_argument($argument) {
    // set up variables to make it easier to reference during the argument.
    $view = &$this->view;
    $handler = &$this->argument;

    ob_start();
    $result = eval($this->options['code']);
    ob_end_clean();
    return $result;
  }

}
