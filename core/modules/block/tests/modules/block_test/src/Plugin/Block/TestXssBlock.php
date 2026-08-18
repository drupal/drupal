<?php

declare(strict_types=1);

namespace Drupal\block_test\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a block containing an XSS payload.
 */
#[Block(
  id: "test_xss",
  admin_label: new TranslatableMarkup("XSS payload"),
)]
class TestXssBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $payload = '<img src=x onerror="window.XSS = true;">';

    return ['#markup' => Markup::create('<input class="block-filter-text" data-element="' . Html::escape($payload) . '">')];
  }

}
