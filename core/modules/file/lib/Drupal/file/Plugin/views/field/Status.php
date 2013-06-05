<?php

/**
 * @file
 * Definition of Drupal\file\Plugin\views\field\Status.
 */

namespace Drupal\file\Plugin\views\field;

use Drupal\Component\Annotation\PluginID;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Field handler to translate a node type into its readable form.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("file_status")
 */
class Status extends FieldPluginBase {

  function render($values) {
    $value = $this->getValue($values);
    return _views_file_status($value);
  }

}
