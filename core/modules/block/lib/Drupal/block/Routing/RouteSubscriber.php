<?php

/**
 * @file
 * Contains \Drupal\block\Routing\RouteSubscriber.
 */

namespace Drupal\block\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides dynamic routes for various block pages.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function routes(RouteCollection $collection) {
    foreach (list_themes(TRUE) as $key => $theme) {
      // The block entity listing page.
      $route = new Route(
        "admin/structure/block/list/$key",
        array(
          '_controller' => '\Drupal\block\Controller\BlockListController::listing',
          'theme' => $key,
        ),
        array(
          '_access_theme' => 'TRUE',
          '_permission' => 'administer blocks',
        )
      );
      $collection->add("block.admin_display_$key", $route);
    }
  }

}
