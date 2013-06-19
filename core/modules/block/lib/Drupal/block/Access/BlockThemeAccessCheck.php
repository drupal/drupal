<?php

/**
 * @file
 * Contains \Drupal\block\Access\BlockThemeAccessCheck.
 */

namespace Drupal\block\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Checks access for displaying block page.
 */
class BlockThemeAccessCheck implements AccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return array_key_exists('_block_themes_access', $route->getRequirements());
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    $theme = $request->attributes->get('theme');
    return user_access('administer blocks') && drupal_theme_access($theme);
  }

}
