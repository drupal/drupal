<?php

declare(strict_types=1);

namespace Drupal\block_test\Plugin\Block;

use Drupal\block_test\PluginForm\EmptyBlockForm;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a block with multiple forms.
 */
#[Block(
  id: "test_multiple_forms_block",
  forms: [
    'secondary' => EmptyBlockForm::class,
  ],
  admin_label: new TranslatableMarkup("Multiple forms test block"),
)]
class TestMultipleFormsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [];
  }

}
