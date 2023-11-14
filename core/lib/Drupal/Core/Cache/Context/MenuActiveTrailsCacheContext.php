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
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface|null $menuActiveTrailService
   *   The menu active trail service.
   */
  public function __construct(protected ?MenuActiveTrailInterface $menuActiveTrailService = NULL) {
    if ($this->menuActiveTrailService === NULL) {
      @trigger_error('Calling ' . __METHOD__ . ' without the $menuActiveTrailService argument is deprecated in drupal:10.2.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3397515', E_USER_DEPRECATED);
      $this->menuActiveTrailService = \Drupal::service('menu.active_trail');
    }
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
