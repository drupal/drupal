<?php

declare(strict_types=1);

namespace Drupal\layout_builder_test\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a class for a context-aware block.
 */
#[Block(
  id:'preview_aware_block',
  admin_label: new TranslatableMarkup('Preview-aware block'),
  category: new TranslatableMarkup('Test'))
]
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
