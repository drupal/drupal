<?php

namespace Drupal\cache_test\Controller;

use Drupal\Core\Url;

/**
 * Controller routines for cache_test routes.
 */
class CacheTestController {

  /**
   * Early renders a URL to test bubbleable metadata bubbling.
   */
  public function urlBubbling() {
    $url = Url::fromRoute('<current>')->setAbsolute();
    return [
      '#markup' => 'This URL is early-rendered: ' . $url->toString() . '. Yet, its bubbleable metadata should be bubbled.',
    ];
  }

  /**
   * Bundle listing tags invalidation.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   *
   * @return array
   *   Renderable array.
   */
  public function bundleTags($entity_type_id, $bundle) {
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
    $entity_ids = $storage->getQuery()->condition('type', $bundle)->execute();
    $page = [];

    $entities = $storage->loadMultiple($entity_ids);
    foreach ($entities as $entity) {
      $page[$entity->id()] = [
        '#markup' => $entity->label(),
      ];
    }
    $page['#cache']['tags'] = [$entity_type_id . '_list:' . $bundle];
    return $page;
  }

}
