<?php

namespace Drupal\views\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * Provides a handler that renders links as dropbutton.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("dropbutton")
 */
class Dropbutton extends Links {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $links = $this->getLinks();

    if (!empty($links)) {
      return [
        '#type' => 'dropbutton',
        '#links' => $links,
      ];
    }
    else {
      return '';
    }
  }

}
