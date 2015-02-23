<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\MenuActiveTrailCacheContext.
 */

namespace Drupal\Core\Cache;

use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Defines the MenuActiveTrailCacheContext service.
 *
 * This class is container-aware to avoid initializing the 'menu.active_trail'
 * service (and its dependencies) when it is not necessary.
 */
class MenuActiveTrailCacheContext extends ContainerAware implements CalculatedCacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t("Active menu trail");
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($menu_name) {
    $active_trail = $this->container->get('menu.active_trail')
      ->getActiveTrailIds($menu_name);
    return 'menu_trail.' . $menu_name . '|' . implode('|', $active_trail);
  }

}
