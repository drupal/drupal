<?php

/**
 * @file
 * Contains \Drupal\custom_block\Plugin\Menu\LocalAction\CustomBlockAddLocalAction.
 */

namespace Drupal\custom_block\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Symfony\Component\HttpFoundation\Request;

/**
 * Modifies the 'Add custom block' local action.
 */
class CustomBlockAddLocalAction extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getOptions(Request $request) {
    $options = parent::getOptions($request);
    // If the route specifies a theme, append it to the query string.
    if ($request->attributes->has('theme')) {
      $options['query']['theme'] = $request->attributes->get('theme');
    }
    return $options;
  }

}
