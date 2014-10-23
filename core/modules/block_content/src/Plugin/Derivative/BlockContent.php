<?php

/**
 * @file
 * Contains \Drupal\block_content\Plugin\Derivative\BlockContent.
 */

namespace Drupal\block_content\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Retrieves block plugin definitions for all custom blocks.
 */
class BlockContent extends DeriverBase {
  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $block_contents = entity_load_multiple('block_content');
    /** @var $block_content \Drupal\block_content\Entity\BlockContent */
    foreach ($block_contents as $block_content) {
      $this->derivatives[$block_content->uuid()] = $base_plugin_definition;
      $this->derivatives[$block_content->uuid()]['admin_label'] = $block_content->label();
      $this->derivatives[$block_content->uuid()]['config_dependencies']['content'] = array(
        $block_content->getConfigDependencyName()
      );
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }
}
