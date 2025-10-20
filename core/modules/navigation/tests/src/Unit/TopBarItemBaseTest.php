<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Unit;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\navigation\TopBarItemBase;
use Drupal\navigation\TopBarRegion;
use Drupal\navigation_test\Plugin\TopBarItem\TopBarItemInstantiation;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\navigation\TopBarItemBase.
 */
#[CoversClass(TopBarItemBase::class)]
#[Group('navigation')]
class TopBarItemBaseTest extends UnitTestCase {

  /**
   * Tests top bar item base.
   *
   * @legacy-covers ::label
   * @legacy-covers ::region
   * @legacy-covers ::weight
   */
  public function testTopBarItemBase(): void {
    $definition = [
      'label' => new TranslatableMarkup('label'),
      'region' => TopBarRegion::Tools,
      'weight' => 0,
    ];

    $top_bar_item_base = new TopBarItemInstantiation([], 'test_top_bar_item_base', $definition);

    $this->assertEquals($definition['label'], $top_bar_item_base->label());
    $this->assertEquals($definition['region'], $top_bar_item_base->region());
    $this->assertEquals($definition['weight'], $top_bar_item_base->weight());
  }

}
