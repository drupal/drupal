<?php

namespace Drupal\block_content\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

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
    if ($route_match->getRouteName() == 'entity.block_content.collection') {
      $options['query']['destination'] = Url::fromRoute('<current>')->toString();
    }
    return $options;
  }

}
