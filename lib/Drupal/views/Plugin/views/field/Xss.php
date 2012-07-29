<?php
/**
 * @file
 * Definition of Drupal\views\Plugin\views\field\Xss
 */
namespace Drupal\views\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * A handler to run a field through simple XSS filtering.
 *
 * @ingroup views_field_handlers
 */

/**
 * @plugin(
 *   plugin_id = "xss"
 * )
 */
class Xss extends FieldPluginBase {
  function render($values) {
    $value = $this->get_value($values);
    return $this->sanitize_value($value, 'xss');
  }
}
