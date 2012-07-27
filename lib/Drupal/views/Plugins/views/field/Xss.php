<?php
/**
 * @file
 * Definition of Drupal\views\Plugins\views\field\Xss
 */
namespace Drupal\views\Plugins\views\field;

use Drupal\views\Plugins\views\field\FieldPluginBase;

/**
 * A handler to run a field through simple XSS filtering.
 *
 * @ingroup views_field_handlers
 */
class Xss extends FieldPluginBase {
  function render($values) {
    $value = $this->get_value($values);
    return $this->sanitize_value($value, 'xss');
  }
}
