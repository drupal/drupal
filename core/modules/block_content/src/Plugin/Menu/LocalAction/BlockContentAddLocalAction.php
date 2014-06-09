<?php

/**
 * @file
 * Contains \Drupal\block_content\Plugin\Menu\LocalAction\BlockContentAddLocalAction.
 */

namespace Drupal\block_content\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Modifies the 'Add custom block' local action.
 */
class BlockContentAddLocalAction extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getOptions(Request $request) {
    $options = parent::getOptions($request);
    // If the route specifies a theme, append it to the query string.
    if ($request->attributes->has('theme')) {
      $options['query']['theme'] = $request->attributes->get('theme');
    }
    // Adds a destination on custom block listing.
    if ($request->attributes->get(RouteObjectInterface::ROUTE_NAME) == 'block_content.list') {
      $options['query']['destination'] = 'admin/structure/block/block-content';
    }
    // Adds a destination on custom block listing.
    if ($request->attributes->get(RouteObjectInterface::ROUTE_NAME) == 'block_content.list') {
      $options['query']['destination'] = 'admin/structure/block/block-content';
    }
    return $options;
  }

}
