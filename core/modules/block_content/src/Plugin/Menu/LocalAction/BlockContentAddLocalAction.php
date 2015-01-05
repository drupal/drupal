<?php

/**
 * @file
 * Contains \Drupal\block_content\Plugin\Menu\LocalAction\BlockContentAddLocalAction.
 */

namespace Drupal\block_content\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Modifies the 'Add custom block' local action.
 */
class BlockContentAddLocalAction extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getOptions(RouteMatchInterface $route_match) {
    $options = parent::getOptions($route_match);
    // If the route specifies a theme, append it to the query string.
    if ($theme = $route_match->getParameter('theme')) {
      $options['query']['theme'] = $theme;
    }
    // Adds a destination on custom block listing.
    if ($route_match->getRouteName() == 'block_content.list') {
      $options['query']['destination'] = 'admin/structure/block/block-content';
    }
    return $options;
  }

}
