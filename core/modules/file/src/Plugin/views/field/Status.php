<?php

/**
 * @file
 * Definition of Drupal\file\Plugin\views\field\Status.
 */

namespace Drupal\file\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to translate a node type into its readable form.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("file_status")
 */
class Status extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return _views_file_status($value);
  }

}
