<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Block\ViewsExposedFilterBlock.
 */

namespace Drupal\views\Plugin\Block;

/**
 * Provides a 'Views Exposed Filter' block.
 *
 * @Block(
 *   id = "views_exposed_filter_block",
 *   admin_label = @Translation("Views Exposed Filter Block"),
 *   derivative = "Drupal\views\Plugin\Derivative\ViewsExposedFilterBlock"
 * )
 */
class ViewsExposedFilterBlock extends ViewsBlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $output = $this->view->display_handler->viewExposedFormBlocks();
    // Before returning the block output, convert it to a renderable array with
    // contextual links.
    $this->addContextualLinks($output, 'exposed_filter');

    return $output;
  }

}
