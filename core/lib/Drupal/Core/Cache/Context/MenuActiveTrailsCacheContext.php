<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\Context\MenuActiveTrailsCacheContext.
 */

namespace Drupal\Core\Cache\Context;

use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Defines the MenuActiveTrailsCacheContext service.
 *
 * This class is container-aware to avoid initializing the 'menu.active_trail'
 * service (and its dependencies) when it is not necessary.
 */
class MenuActiveTrailsCacheContext extends ContainerAware implements CalculatedCacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t("Active menu trail");
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($menu_name = NULL) {
    $active_trail = $this->container->get('menu.active_trail')
      ->getActiveTrailIds($menu_name);
    return 'menu_trail.' . $menu_name . '|' . implode('|', $active_trail);
  }

}
