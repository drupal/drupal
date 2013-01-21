<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\block\block\ViewsExposedFilterBlock.
 */

namespace Drupal\views\Plugin\block\block;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'Views Exposed Filter' block.
 *
 * @Plugin(
 *   id = "views_exposed_filter_block",
 *   subject = @Translation("Views Exposed Filter Block"),
 *   module = "views",
 *   derivative = "Drupal\views\Plugin\Derivative\ViewsExposedFilterBlock"
 * )
 */
class ViewsExposedFilterBlock extends ViewsBlock {

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {
    $type = 'exp';
    $output = $this->view->display_handler->viewSpecialBlocks($type);
    // Before returning the block output, convert it to a renderable array with
    // contextual links.
    views_add_block_contextual_links($output, $this->view, $this->display_id, 'special_block_' . $type);
    $this->view->destroy();
    return $output;
  }

}
