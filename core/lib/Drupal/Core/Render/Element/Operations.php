<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Operations.
 */

namespace Drupal\Core\Render\Element;

/**
 * Provides a render element for a set of operations links.
 *
 * This is a special case of \Drupal\Core\Render\Element\Dropbutton; the only
 * difference is that it offers themes the possibility to render it differently
 * through a theme suggestion.
 *
 * @see \Drupal|Core\Render\Element\DropButton
 *
 * @RenderElement("operations")
 */
class Operations extends Dropbutton {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return array(
      '#theme' => 'links__dropbutton__operations',
    ) + parent::getInfo();
  }

}
