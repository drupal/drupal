<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\argument\FieldList.
 */

namespace Drupal\views\Plugin\views\argument;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Field\AllowedTagsXssTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\argument\NumericArgument;

/**
 * Argument handler for list field to show the human readable name in the
 * summary.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("field_list")
 */
class FieldList extends NumericArgument {

  use AllowedTagsXssTrait;

  /**
   * Stores the allowed values of this field.
   *
   * @var array
   */
  var $allowed_values = NULL;

  /**
   * Overrides \Drupal\views\Plugin\views\argument\ArgumentPluginBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $field_storage_definitions = \Drupal::entityManager()->getFieldStorageDefinitions($this->definition['entity_type']);
    $field_storage = $field_storage_definitions[$this->definition['field_name']];
    $this->allowed_values = options_allowed_values($field_storage);
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['summary']['contains']['human'] = array('default' => FALSE);

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['summary']['human'] = array(
      '#title' => $this->t('Display list value as human readable'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['summary']['human'],
      '#states' => array(
        'visible' => array(
          ':input[name="options[default_action]"]' => array('value' => 'summary'),
        ),
      ),
    );
  }

  public function summaryName($data) {
    $value = $data->{$this->name_alias};
    // If the list element has a human readable name show it,
    if (isset($this->allowed_values[$value]) && !empty($this->options['summary']['human'])) {
      return $this->fieldFilterXss($this->allowed_values[$value]);
    }
    // else fallback to the key.
    else {
      return SafeMarkup::checkPlain($value);
    }
  }

}
