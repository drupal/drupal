<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\argument_default\Raw.
 */

namespace Drupal\views\Plugin\views\argument_default;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Default argument plugin to use the raw value from the URL.
 *
 * @ingroup views_argument_default_plugins
 *
 * @Plugin(
 *   id = "raw",
 *   title = @Translation("Raw value from URL")
 * )
 */
class Raw extends ArgumentDefaultPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['index'] = array('default' => '');
    $options['use_alias'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    // Using range(1, 10) will create an array keyed 0-9, which allows arg() to
    // properly function since it is also zero-based.
    $form['index'] = array(
      '#type' => 'select',
      '#title' => t('Path component'),
      '#default_value' => $this->options['index'],
      '#options' => range(1, 10),
      '#description' => t('The numbering starts from 1, e.g. on the page admin/structure/types, the 3rd path component is "types".'),
    );
    $form['use_alias'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use path alias'),
      '#default_value' => $this->options['use_alias'],
      '#description' => t('Use path alias instead of internal path.'),
    );
  }

  public function getArgument() {
    $path = NULL;
    if ($this->options['use_alias']) {
      $path = drupal_get_path_alias();
    }
    if ($arg = arg($this->options['index'], $path)) {
      return $arg;
    }
  }

}
