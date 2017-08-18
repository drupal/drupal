<?php

namespace Drupal\outside_in\Access;

use Drupal\block\BlockInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Determines whether the requested block has an 'off_canvas' form.
 *
 * @internal
 */
class BlockPluginHasOffCanvasFormAccessCheck implements AccessInterface {

  /**
   * Checks access for accessing a block's 'off_canvas' form.
   *
   * @param \Drupal\block\BlockInterface $block
   *   The block whose 'off_canvas' form is being accessed.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(BlockInterface $block) {
    /** @var \Drupal\Core\Block\BlockPluginInterface $block_plugin */
    $block_plugin = $block->getPlugin();
    return $this->accessBlockPlugin($block_plugin);
  }

  /**
   * Checks access for accessing a block plugin's 'off_canvas' form.
   *
   * @param \Drupal\Core\Block\BlockPluginInterface $block_plugin
   *   The block plugin whose 'off_canvas' form is being accessed.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @see outside_in_preprocess_block()
   */
  public function accessBlockPlugin(BlockPluginInterface $block_plugin) {
    return AccessResult::allowedIf($block_plugin instanceof PluginWithFormsInterface && $block_plugin->hasFormClass('off_canvas'));
  }

}
