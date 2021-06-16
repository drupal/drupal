<?php

namespace Drupal\block_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with multiple forms.
 *
 * @Block(
 *   id = "test_multiple_forms_block",
 *   forms = {
 *     "secondary" = "\Drupal\block_test\PluginForm\EmptyBlockForm"
 *   },
 *   admin_label = @Translation("Multiple forms test block")
 * )
 */
class TestMultipleFormsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [];
  }

}
