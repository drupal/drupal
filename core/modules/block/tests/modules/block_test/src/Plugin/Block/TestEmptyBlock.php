<?php

declare(strict_types=1);

namespace Drupal\block_test\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a block that returns an empty array.
 */
#[Block(
  id: "test_empty",
  admin_label: new TranslatableMarkup("Test Empty block"),
)]
class TestEmptyBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [];
  }

}
