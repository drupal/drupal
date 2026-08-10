<?php

namespace Drupal\options\Plugin\views\argument;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\options\OptionsAllowedValuesInterface;
use Drupal\views\Attribute\ViewsArgument;
use Drupal\views\FieldAPIHandlerTrait;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\argument\StringArgument;

/**
 * Argument handler for list field to show the human readable name in summary.
 *
 * @ingroup views_argument_handlers
 */
#[ViewsArgument(
  id: 'string_list_field',
)]
class StringListField extends StringArgument {

  use FieldAPIHandlerTrait;

  /**
   * Stores the allowed values of this field.
   *
   * @var array
   */
  protected $allowedValues = NULL;

  /**
   * The options allowed values service.
   */
  protected OptionsAllowedValuesInterface $optionsAllowedValues;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ?OptionsAllowedValuesInterface $options_allowed_values = NULL,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (!$options_allowed_values) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $options_allowed_values argument is deprecated in drupal:11.5.0 and it will be required in drupal:12.0.0. See https://www.drupal.org/node/3572185', E_USER_DEPRECATED);
      $options_allowed_values = \Drupal::service(OptionsAllowedValuesInterface::class);
    }
    $this->optionsAllowedValues = $options_allowed_values;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, ?array &$options = NULL) {
    parent::init($view, $display, $options);

    $field_storage = $this->getFieldStorageDefinition();
    $this->allowedValues = $this->optionsAllowedValues->getAllowedValues($field_storage);
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
      $value = $this->allowedValues[$value];
    }
    return FieldFilteredMarkup::create($this->caseTransform($value, $this->options['case']));
  }

}
