<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\views\row\NodeRow.
 */

namespace Drupal\node\Plugin\views\row;

use Drupal\views\Plugin\views\row\EntityRow;

/**
 * Plugin which performs a node_view on the resulting object.
 *
 * Most of the code on this object is in the theme function.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "entity:node",
 * )
 */
class NodeRow extends EntityRow {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['view_mode']['default'] = 'teaser';

    return $options;
  }

}
