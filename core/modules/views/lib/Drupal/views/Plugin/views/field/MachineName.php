<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\field\MachineName.
 */

namespace Drupal\views\Plugin\views\field;

use Drupal\Component\Annotation\PluginID;

/**
 * Field handler whichs allows to show machine name content as human name.
 * @ingroup views_field_handlers
 *
 * Definition items:
 * - options callback: The function to call in order to generate the value options. If omitted, the options 'Yes' and 'No' will be used.
 * - options arguments: An array of arguments to pass to the options callback.
 *
 * @PluginID("machine_name")
 */
class MachineName extends FieldPluginBase {

  /**
   * @var array Stores the available options.
   */
  var $value_options;

  public function getValueOptions() {
    if (isset($this->value_options)) {
      return;
    }

    if (isset($this->definition['options callback']) && is_callable($this->definition['options callback'])) {
      if (isset($this->definition['options arguments']) && is_array($this->definition['options arguments'])) {
        $this->value_options = call_user_func_array($this->definition['options callback'], $this->definition['options arguments']);
      }
      else {
        $this->value_options = call_user_func($this->definition['options callback']);
      }
    }
    else {
      $this->value_options = array();
    }
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['machine_name'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['machine_name'] = array(
      '#title' => t('Output machine name'),
      '#description' => t('Display field as machine name.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['machine_name']),
    );
  }

  public function preRender(&$values) {
    $this->getValueOptions();
  }

  public function render($values) {
    $value = $values->{$this->field_alias};
    if (!empty($this->options['machine_name']) || !isset($this->value_options[$value])) {
      $result = check_plain($value);
    }
    else {
      $result = $this->value_options[$value];
    }

    return $result;
  }

}
