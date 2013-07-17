<?php

/**
 * @file
 * Contains \Drupal\block\Access\BlockThemeAccessCheck.
 */

namespace Drupal\block\Access;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Checks access for displaying block page.
 */
class BlockThemeAccessCheck implements StaticAccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_block_themes_access');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    $theme = $request->attributes->get('theme');
    return user_access('administer blocks') && drupal_theme_access($theme);
  }

}
