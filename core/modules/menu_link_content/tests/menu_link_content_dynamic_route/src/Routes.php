<?php

namespace Drupal\menu_link_content_dynamic_route;

/**
 * Provides dynamic routes for test purposes.
 */
class Routes {

  public function dynamic() {
    return \Drupal::state()->get('menu_link_content_dynamic_route.routes', []);
  }

}
