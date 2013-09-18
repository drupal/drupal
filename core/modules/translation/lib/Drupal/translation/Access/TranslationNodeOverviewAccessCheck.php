<?php

/**
 * @file
 * Contains \Drupal\translation\Access\TranslationNodeOverviewAccessCheck.
 */

namespace Drupal\translation\Access;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Provides an access checker for the node translation tab.
 */
class TranslationNodeOverviewAccessCheck implements StaticAccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_access_translation_tab');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    $key = $route->getRequirement('_access_translation_tab');
    if ($request->attributes->has($key)) {
      // @todo Remove _translation_tab_access().
      return _translation_tab_access($request->attributes->get($key)) ? static::ALLOW : static::DENY;
    }
    return static::DENY;
  }

}
