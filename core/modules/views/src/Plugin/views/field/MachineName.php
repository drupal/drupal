<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\field\MachineName.
 */

namespace Drupal\views\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;

/**
 * Field handler which allows to show machine name content as human name.
 * @ingroup views_field_handlers
 *
 * Definition items:
 * - options callback: The function to call in order to generate the value options. If omitted, the options 'Yes' and 'No' will be used.
 * - options arguments: An array of arguments to pass to the options callback.
 *
 * @ViewsField("machine_name")
 */
class MachineName extends FieldPluginBase {

  /**
   * @var array Stores the available options.
   */
  protected $valueOptions;

  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return;
    }

    if (isset($this->definition['options callback']) && is_callable($this->definition['options callback'])) {
      if (isset($this->definition['options arguments']) && is_array($this->definition['options arguments'])) {
        $this->valueOptions = call_user_func_array($this->definition['options callback'], $this->definition['options arguments']);
      }
      else {
        $this->valueOptions = call_user_func($this->definition['options callback']);
      }
    }
    else {
      $this->valueOptions = array();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['machine_name'] = array('default' => FALSE);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['machine_name'] = array(
      '#title' => $this->t('Output machine name'),
      '#description' => $this->t('Display field as machine name.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['machine_name']),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    $this->getValueOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $values->{$this->field_alias};
    if (!empty($this->options['machine_name']) || !isset($this->valueOptions[$value])) {
      $result = $this->sanitizeValue($value);
    }
    else {
      $result = $this->valueOptions[$value];
    }

    return $result;
  }

}
