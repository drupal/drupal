<?php

/**
 * @file
 * Contains \Drupal\system\EventSubscriber\RouteSubscriber.
 */

namespace Drupal\system\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides dynamic routes for theme administration.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function routes(RouteCollection $collection) {
    foreach (list_themes() as $theme) {
      if (!empty($theme->status)) {
        $route = new Route('admin/appearance/settings/' . $theme->name, array(
          '_form' => '\Drupal\system\Form\ThemeSettingsForm', 'theme_name' => $theme->name), array(
          '_permission' => 'administer themes',
        ));
        $collection->add('system.theme_settings_' . $theme->name, $route);
      }
    }
  }

}
