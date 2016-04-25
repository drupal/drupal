<?php

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Defines the MenuActiveTrailsCacheContext service.
 *
 * This class is container-aware to avoid initializing the 'menu.active_trails'
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
    if (!$menu_name) {
      throw new \LogicException('No menu name provided for menu.active_trails cache context.');
    }

    $active_trail = $this->container->get('menu.active_trail')
      ->getActiveTrailIds($menu_name);
    return 'menu_trail.' . $menu_name . '|' . implode('|', $active_trail);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($menu_name = NULL) {
    if (!$menu_name) {
      throw new \LogicException('No menu name provided for menu.active_trails cache context.');
    }
    $cacheable_metadata = new CacheableMetadata();
    return $cacheable_metadata->setCacheTags(["config:system.menu.$menu_name"]);
  }

}
