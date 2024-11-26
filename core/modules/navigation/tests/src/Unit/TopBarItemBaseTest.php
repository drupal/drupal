<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Unit;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\navigation\TopBarRegion;
use Drupal\navigation_test\Plugin\TopBarItem\TopBarItemInstantiation;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\navigation\TopBarItemBase
 *
 * @group navigation
 */
class TopBarItemBaseTest extends UnitTestCase {

  /**
   * @covers ::label
   * @covers ::region
   */
  public function testTopBarItemBase(): void {
    $definition = [
      'label' => new TranslatableMarkup('label'),
      'region' => TopBarRegion::Tools,
    ];

    $top_bar_item_base = new TopBarItemInstantiation([], 'test_top_bar_item_base', $definition);

    $this->assertEquals($definition['label'], $top_bar_item_base->label());
    $this->assertEquals($definition['region'], $top_bar_item_base->region());
  }

}
