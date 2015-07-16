<?php

/**
 * @file
 * Contains \Drupal\cache_test\Controller\CacheTestController.
 */

namespace Drupal\cache_test\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Render\HtmlResponse;
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
      '#markup' => SafeMarkup::format('This URL is early-rendered: !url. Yet, its bubbleable metadata should be bubbled.', ['!url' => $url->toString()])
    ];
  }

}
