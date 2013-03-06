<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\block\block\ViewsExposedFilterBlock.
 */

namespace Drupal\views\Plugin\block\block;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'Views Exposed Filter' block.
 *
 * @Plugin(
 *   id = "views_exposed_filter_block",
 *   admin_label = @Translation("Views Exposed Filter Block"),
 *   module = "views",
 *   derivative = "Drupal\views\Plugin\Derivative\ViewsExposedFilterBlock"
 * )
 */
class ViewsExposedFilterBlock extends ViewsBlock {

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {
    $output = $this->view->display_handler->viewExposedFormBlocks();
    // Before returning the block output, convert it to a renderable array with
    // contextual links.
    $this->addContextualLinks($output, 'exposed_filter');

    $this->view->destroy();
    return $output;
  }

}
