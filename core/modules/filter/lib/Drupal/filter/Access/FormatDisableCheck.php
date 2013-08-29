<?php

/**
 * @file
 * Contains \Drupal\filter\Access\FormatDisableCheck.
 */

namespace Drupal\filter\Access;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Checks access for disabling text formats.
 */
class FormatDisableCheck implements StaticAccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_filter_disable_format_access');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    $format = $request->attributes->get('filter_format');
    return ($format && !$format->isFallbackFormat()) ? static::ALLOW : static::DENY;
  }

}
