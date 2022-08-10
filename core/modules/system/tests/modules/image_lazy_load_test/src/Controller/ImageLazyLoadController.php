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
    $images['with-dimensions'] = [
      '#theme' => 'image',
      '#uri' => '/core/themes/olivero/logo.svg',
      '#alt' => 'Image lazy load testing image',
      '#prefix' => '<div id="with-dimensions">',
      '#suffix' => '</div>',
      '#width' => '50%',
      '#height' => '50%',
    ];

    $images['without-dimensions'] = [
      '#theme' => 'image',
      '#uri' => '/core/themes/olivero/logo.svg',
      '#alt' => 'Image lazy load testing image without dimensions',
      '#prefix' => '<div id="without-dimensions">',
      '#suffix' => '</div>',
    ];

    $images['override-loading-attribute'] = [
      '#theme' => 'image',
      '#uri' => '/core/themes/olivero/logo.svg',
      '#alt' => 'Image lazy load test loading attribute can be overridden',
      '#prefix' => '<div id="override-loading-attribute">',
      '#suffix' => '</div>',
      '#width' => '50%',
      '#height' => '50%',
    ];

    $images['override-loading-attribute']['#attributes']['loading'] = 'eager';

    return $images;
  }

}
