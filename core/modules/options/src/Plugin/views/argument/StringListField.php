<?php

/**
 * @file
 * Contains \Drupal\options\Plugin\views\argument\StringListField.
 */

namespace Drupal\options\Plugin\views\argument;

use Drupal\Core\Field\AllowedTagsXssTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\FieldAPIHandlerTrait;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\argument\StringArgument;
use Drupal\Component\Utility\String as StringUtility;

/**
 * Argument handler for list field to show the human readable name in summary.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("string_list_field")
 */
class StringListField extends StringArgument {

  use AllowedTagsXssTrait;
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
      return $this->caseTransform($this->fieldFilterXss($this->allowedValues[$value]), $this->options['case']);
    }
    // Else, fallback to the key.
    else {
      return $this->caseTransform(StringUtility::checkPlain($value), $this->options['case']);
    }
  }

}
