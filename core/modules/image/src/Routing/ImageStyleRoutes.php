<?php

/**
 * @file
 * Contains \Drupal\image\EventSubscriber\RouteSubscriber.
 */

namespace Drupal\image\Routing;

use Symfony\Component\Routing\Route;

/**
 * Defines a route subscriber to register a url for serving image styles.
 */
class ImageStyleRoutes {

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes() {
    $routes = array();
    // Generate image derivatives of publicly available files. If clean URLs are
    // disabled image derivatives will always be served through the menu system.
    // If clean URLs are enabled and the image derivative already exists, PHP
    // will be bypassed.
    $directory_path = file_stream_wrapper_get_instance_by_scheme('public')->getDirectoryPath();

    $routes['image.style_public'] = new Route(
      '/' . $directory_path . '/styles/{image_style}/{scheme}',
      array(
        '_controller' => 'Drupal\image\Controller\ImageStyleDownloadController::deliver',
      ),
      array(
        '_access' => 'TRUE',
      )
    );
    return $routes;
  }

}
