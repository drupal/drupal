<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\views\area\ListingEmpty.
 */

namespace Drupal\node\Plugin\views\area;

use Drupal\Component\Annotation\PluginID;
use Drupal\views\Plugin\views\area\AreaPluginBase;

/**
 * Defines an area plugin to display a node/add link.
 *
 * @ingroup views_area_handlers
 *
 * @PluginID("node_listing_empty")
 */
class ListingEmpty extends AreaPluginBase {

  /**
   * Implements \Drupal\views\Plugin\views\area\AreaPluginBase::render().
   */
  public function render($empty = FALSE) {
    if (!$empty || !empty($this->options['empty'])) {
      $element = array(
        '#theme' => 'links',
        '#links' => array(
          array(
            'href' => 'node/add',
            'title' => t('Add new content')
          )
        ) ,
        '#access' => _node_add_access()
      );
      return $element;
    }
    return array();
  }

}
