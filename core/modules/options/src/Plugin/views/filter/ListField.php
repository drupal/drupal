<?php

/**
 * @file
 * Contains \Drupal\field\Plugin\views\filter\ListField.
 */

namespace Drupal\options\Plugin\views\filter;

use Drupal\field\Views\FieldAPIHandlerTrait;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\views\ViewExecutable;

/**
 * Filter handler which uses list-fields as options.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("list_field")
 */
class ListField extends ManyToOne {

  use FieldAPIHandlerTrait;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $field_storage = $this->getFieldStorageDefinition();
    // Set valueOptions here so getValueOptions() will just return it.
    $this->valueOptions = options_allowed_values($field_storage);
  }

}
