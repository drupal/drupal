<?php

/**
 * @file
 * Contains \Drupal\image\EventSubscriber\RouteSubscriber.
 */

namespace Drupal\image\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines a route subscriber to register a url for serving image styles.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function routes(RouteCollection $collection) {
    // Generate image derivatives of publicly available files. If clean URLs are
    // disabled image derivatives will always be served through the menu system.
    // If clean URLs are enabled and the image derivative already exists, PHP
    // will be bypassed.
    $directory_path = file_stream_wrapper_get_instance_by_scheme('public')->getDirectoryPath();

    $route = new Route('/' . $directory_path . '/styles/{image_style}/{scheme}',
      array(
        '_controller' => 'Drupal\image\Controller\ImageStyleDownloadController::deliver',
      ),
      array(
        '_access' => 'TRUE',
      )
    );
    $collection->add('image.style_public', $route);
  }

}
