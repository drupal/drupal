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
    return 'is_layout_builder_ui.' . (int) (strpos($this->routeMatch->getRouteName(), 'layout_builder.') !== 0);
  }

}
