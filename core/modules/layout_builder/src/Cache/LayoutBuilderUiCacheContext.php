<?php

namespace Drupal\layout_builder\Cache;

use Drupal\Core\Cache\Context\RouteNameCacheContext;

/**
 * Determines if an entity is being viewed in the Layout Builder UI.
 *
 * Cache context ID: 'route.name.is_layout_builder_ui'.
 *
 * @internal
 *   Tagged services are internal.
 */
class LayoutBuilderUiCacheContext extends RouteNameCacheContext {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Layout Builder user interface');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    $route_name = $this->routeMatch->getRouteName();
    if ($route_name && str_starts_with($route_name, 'layout_builder.')) {
      return 'is_layout_builder_ui.0';
    }
    return 'is_layout_builder_ui.1';
  }

}
