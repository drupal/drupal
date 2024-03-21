<?php

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Menu\MenuActiveTrailInterface;

/**
 * Defines the MenuActiveTrailsCacheContext service.
 */
class MenuActiveTrailsCacheContext implements CalculatedCacheContextInterface {

  /**
   * Constructs a MenuActiveTrailsCacheContext object.
   *
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface $menuActiveTrailService
   *   The menu active trail service.
   */
  public function __construct(protected MenuActiveTrailInterface $menuActiveTrailService) {
  }

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

    $active_trail = $this->menuActiveTrailService->getActiveTrailIds($menu_name);
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
