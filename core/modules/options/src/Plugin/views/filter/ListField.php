<?php

namespace Drupal\options\Plugin\views\filter;

use Drupal\options\OptionsAllowedValuesInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\FieldAPIHandlerTrait;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\views\ViewExecutable;

/**
 * Filter handler which uses list-fields as options.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("list_field")]
class ListField extends ManyToOne {

  use FieldAPIHandlerTrait;

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
    // Set valueOptions here so getValueOptions() will just return it.
    $this->valueOptions = $this->optionsAllowedValues->getAllowedValues($field_storage);
  }

}
