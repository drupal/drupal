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

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected OptionsAllowedValuesInterface $optionsAllowedValues,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

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
