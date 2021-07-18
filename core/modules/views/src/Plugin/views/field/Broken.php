<?php

namespace Drupal\views\Plugin\views\field;

use Drupal\views\Plugin\views\BrokenHandlerTrait;
use Drupal\views\ResultRow;

/**
 * A special handler to take the place of missing or broken handlers.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("broken")
 */
class Broken extends FieldPluginBase {
  use BrokenHandlerTrait;

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return '';
  }

}
