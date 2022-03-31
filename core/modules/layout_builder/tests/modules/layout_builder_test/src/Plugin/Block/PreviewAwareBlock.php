<?php

namespace Drupal\layout_builder_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Defines a class for a context-aware block.
 *
 * @Block(
 *   id = "preview_aware_block",
 *   admin_label = "Preview-aware block",
 *   category = "Test",
 * )
 */
class PreviewAwareBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $markup = $this->t('This block is being rendered normally.');

    if ($this->inPreview) {
      $markup = $this->t('This block is being rendered in preview mode.');
    }

    return [
      '#markup' => $markup,
    ];
  }

}
