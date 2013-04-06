<?php

/**
 * @file
 * Contains \Drupal\filter\Access\FormatDisableCheck.
 */

namespace Drupal\filter\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Checks access for disabling text formats.
 */
class FormatDisableCheck implements AccessCheckInterface {

  /**
   * Implements \Drupal\Core\Access\AccessCheckInterface::applies().
   */
  public function applies(Route $route) {
    return array_key_exists('_filter_disable_format_access', $route->getRequirements());
  }

  /**
   * Implements \Drupal\Core\Access\AccessCheckInterface::access().
   */
  public function access(Route $route, Request $request) {
    if ($format = $request->attributes->get('filter_format')) {
      return user_access('administer filters') && ($format->format != filter_fallback_format());
    }

    return FALSE;
  }

}
