<?php

declare(strict_types=1);

namespace Drupal\navigation_test\Plugin\TopBarItem;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\navigation\Attribute\TopBarItem;
use Drupal\navigation\TopBarItemBase;
use Drupal\navigation\TopBarRegion;

/**
 * Provides a top bar item plugin for testing the top bar.
 */
#[TopBarItem(
  id: 'test_item',
  region: TopBarRegion::Actions,
  label: new TranslatableMarkup('Test Item'),
)]
class TopBarItemInstantiation extends TopBarItemBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#markup' => 'Top Bar Item',
    ];
  }

}
