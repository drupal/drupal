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

}
