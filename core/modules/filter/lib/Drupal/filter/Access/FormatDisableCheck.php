<?php

/**
 * @file
 * Contains \Drupal\filter\Access\FormatDisableCheck.
 */

namespace Drupal\filter\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Checks access for disabling text formats.
 */
class FormatDisableCheck implements AccessInterface {

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    $format = $request->attributes->get('filter_format');
    return ($format && !$format->isFallbackFormat()) ? static::ALLOW : static::DENY;
  }

}
