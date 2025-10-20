<?php

declare(strict_types=1);

namespace Drupal\navigation_test\Plugin\TopBarItem;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\navigation\Attribute\TopBarItem;
use Drupal\navigation\TopBarItemBase;
use Drupal\navigation\TopBarRegion;

/**
 * Provides a top bar item plugin for testing the top bar item weight.
 */
#[TopBarItem(
  id: 'test_item_high',
  region: TopBarRegion::Context,
  label: new TranslatableMarkup('High Weight'),
  weight: 10,
)]
class TopBarItemHigh extends TopBarItemBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#markup' => '<span class="top-bar__title" data-plugin-id="test_item_high">High Weight</span>',
    ];
  }

}
