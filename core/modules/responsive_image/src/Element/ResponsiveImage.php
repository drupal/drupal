<?php

namespace Drupal\responsive_image\Element;

use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\Element\RenderElementBase;

/**
 * Provides a responsive image element.
 */
#[RenderElement('responsive_image')]
class ResponsiveImage extends RenderElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'responsive_image',
    ];
  }

}
