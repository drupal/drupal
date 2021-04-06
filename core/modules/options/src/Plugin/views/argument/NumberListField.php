<?php

namespace Drupal\options\Plugin\views\argument;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\FieldAPIHandlerTrait;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\argument\NumericArgument;

/**
 * Argument handler for list field to show human readable name in the summary.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("number_list_field")
 */
class NumberListField extends NumericArgument {

  use FieldAPIHandlerTrait;

  /**
   * Stores the allowed values of this field.
   *
   * @var array
   */
  protected $allowedValues = NULL;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $field_storage = $this->getFieldStorageDefinition();
    $this->allowedValues = options_allowed_values($field_storage);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['summary']['contains']['human'] = ['default' => FALSE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['summary']['human'] = [
      '#title' => $this->t('Display list value as human readable'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['summary']['human'],
      '#states' => [
        'visible' => [
          ':input[name="options[default_action]"]' => ['value' => 'summary'],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function summaryName($data) {
    $value = $data->{$this->name_alias};
    // If the list element has a human readable name show it.
    if (isset($this->allowedValues[$value]) && !empty($this->options['summary']['human'])) {
      return FieldFilteredMarkup::create($this->allowedValues[$value]);
    }
    // Else, fallback to the key.
    else {
      return $value;
    }
  }

}
