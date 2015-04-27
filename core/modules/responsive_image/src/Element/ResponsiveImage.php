<?php
/**
 * @file
 * Contains \Drupal\responsive_image\Element\ResponsiveImage.
 */

namespace Drupal\responsive_image\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a responsive image element.
 *
 * @RenderElement("responsive_image")
 */
class ResponsiveImage extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'responsive_image',
      '#attached' => [
        'library' => ['core/picturefill'],
      ],
    ];
  }

}
