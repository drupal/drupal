<?php

namespace Drupal\image_lazy_load_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * The ImageLazyLoadController class.
 */
class ImageLazyLoadController extends ControllerBase {

  /**
   * Render an image using image theme.
   *
   * @return array
   *   The render array.
   */
  public function renderImage() {
    return [
      '#theme' => 'image',
      '#uri' => '/core/themes/bartik/logo.svg',
      '#alt' => 'Image lazy load testing image',
      '#width' => '50%',
      '#height' => '50%',
    ];
  }

}
