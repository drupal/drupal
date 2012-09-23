<?php

/**
 * @file
 * Definition of Views\field\Plugin\views\argument\ListString.
 */

namespace Views\field\Plugin\views\argument;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\argument\String;
use Drupal\Core\Annotation\Plugin;

/**
 * Argument handler for list field to show the human readable name in the
 * summary.
 *
 * @ingroup views_argument_handlers
 *
 * @Plugin(
 *   id = "field_list_string",
 *   module = "field"
 * )
 */
class ListString extends String {

  /**
   * Stores the allowed values of this field.
   *
   * @var array
   */
  var $allowed_values = NULL;

  public function init(ViewExecutable $view, &$options) {
    parent::init($view, $options);
    $field = field_info_field($this->definition['field_name']);
    $this->allowed_values = options_allowed_values($field);
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['summary']['contains']['human'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['summary']['human'] = array(
      '#title' => t('Display list value as human readable'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['summary']['human'],
      '#states' => array(
        'visible' => array(
          ':input[name="options[default_action]"]' => array('value' => 'summary'),
        ),
      ),
    );
  }


  function summary_name($data) {
    $value = $data->{$this->name_alias};
    // If the list element has a human readable name show it,
    if (isset($this->allowed_values[$value]) && !empty($this->options['summary']['human'])) {
      return $this->caseTransform(field_filter_xss($this->allowed_values[$value]), $this->options['case']);
    }
    // else fallback to the key.
    else {
      return $this->caseTransform(check_plain($value), $this->options['case']);
    }
  }

}
