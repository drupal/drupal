<?php

/**
 * @file
 * Definition of views_handler_field_file_extension.
 */

namespace Drupal\file\Plugin\views\field;

use Drupal\Component\Annotation\Plugin;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Returns a pure file extension of the file, for example 'module'.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "file_extension",
 *   module = "file"
 * )
 */
class Extension extends FieldPluginBase {

  function render($values) {
    $value = $this->get_value($values);
    if (preg_match('/\.([^\.]+)$/', $value, $match)) {
      return $this->sanitizeValue($match[1]);
    }
  }

}
