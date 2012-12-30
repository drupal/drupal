<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\field\Dropbutton.
 */

namespace Drupal\views\Plugin\views\field;

use Drupal\Core\Annotation\Plugin;

/**
 * Provides a handler that renders links as dropbutton.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "dropbutton"
 * )
 */
class Dropbutton extends Links {

  /**
   * Render the dropdown button.
   */
  public function render($values) {
    $links = $this->getLinks();

    if (!empty($links)) {
      return array(
        '#type' => 'dropbutton',
        '#links' => $links,
      );
    }
    else {
      return '';
    }
  }

}
