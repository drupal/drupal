<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\field\Xss
 */

namespace Drupal\views\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * A handler to run a field through simple XSS filtering.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("xss")
 */
class Xss extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->sanitizeValue($value, 'xss');
  }

}
