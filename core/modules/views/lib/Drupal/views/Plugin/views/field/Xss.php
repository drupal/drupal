<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\field\Xss
 */

namespace Drupal\views\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Component\Annotation\PluginID;

/**
 * A handler to run a field through simple XSS filtering.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("xss")
 */
class Xss extends FieldPluginBase {

  public function render($values) {
    $value = $this->getValue($values);
    return $this->sanitizeValue($value, 'xss');
  }

}
