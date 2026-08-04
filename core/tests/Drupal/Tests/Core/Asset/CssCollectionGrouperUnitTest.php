<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Asset;

use Drupal\Core\Asset\CssCollectionGrouper;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the CSS asset collection grouper.
 */
#[Group('Asset')]
class CssCollectionGrouperUnitTest extends UnitTestCase {

  /**
   * A CSS asset grouper.
   *
   * @var \Drupal\Core\Asset\CssCollectionGrouper
   */
  protected $grouper;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->grouper = new CssCollectionGrouper();
  }

  /**
   * Tests \Drupal\Core\Asset\CssCollectionGrouper.
   */
  public function testGrouper(): void {
    $css_assets = [
      'system.base.css' => [
        'group' => -100,
        'type' => 'file',
        'weight' => 0.012,
        'media' => 'all',
        'preprocess' => TRUE,
        'aggregate_target' => ['css' => FALSE],
        'data' => 'core/modules/system/system.base.css',
        'basename' => 'system.base.css',
      ],
      'js.module.css' => [
        'group' => -100,
        'type' => 'file',
        'weight' => 0.013,
        'media' => 'all',
        'preprocess' => TRUE,
        'aggregate_target' => ['css' => FALSE],
        'data' => 'core/modules/system/js.module.css',
        'basename' => 'js.module.css',
      ],
      'jquery.ui.core.css' => [
        'group' => -100,
        'type' => 'file',
        'weight' => 0.004,
        'media' => 'screen',
        'preprocess' => TRUE,
        'aggregate_target' => ['css' => FALSE],
        'data' => 'core/misc/ui/themes/base/jquery.ui.core.css',
        'basename' => 'jquery.ui.core.css',
      ],
      'field.css' => [
        'group' => 0,
        'type' => 'file',
        'weight' => 0.011,
        'media' => 'all',
        'preprocess' => TRUE,
        'aggregate_target' => ['css' => FALSE],
        'data' => 'core/modules/field/theme/field.css',
        'basename' => 'field.css',
      ],
      'external.css' => [
        'group' => 0,
        'type' => 'external',
        'weight' => 0.009,
        'media' => 'all',
        'preprocess' => TRUE,
        'aggregate_target' => ['css' => FALSE],
        'data' => 'http://example.com/external.css',
        'basename' => 'external.css',
      ],
      'elements.css' => [
        'group' => 100,
        'media' => 'all',
        'type' => 'file',
        'weight' => 0.001,
        'preprocess' => TRUE,
        'aggregate_target' => ['css' => FALSE],
        'data' => 'core/themes/example/css/base/elements.css',
        'basename' => 'elements.css',
      ],
      'print.css' => [
        'group' => 100,
        'media' => 'print',
        'type' => 'file',
        'weight' => 0.003,
        'preprocess' => TRUE,
        'aggregate_target' => ['css' => FALSE],
        'data' => 'core/themes/example/css/print.css',
        'basename' => 'print.css',
      ],
    ];

    $groups = $this->grouper->group($css_assets);

    $this->assertCount(4, $groups, "4 groups created.");

    // Check group 1.
    $group = $groups[0];
    $this->assertSame('file', $group['type']);
    $this->assertSame('all', $group['media']);
    $this->assertTrue($group['preprocess']);
    $this->assertCount(4, $group['items']);
    $this->assertContainsEquals($css_assets['system.base.css'], $group['items']);
    $this->assertContainsEquals($css_assets['js.module.css'], $group['items']);
    $this->assertContainsEquals($css_assets['jquery.ui.core.css'], $group['items']);
    $this->assertContainsEquals($css_assets['field.css'], $group['items']);

    // Check group 3.
    $group = $groups[1];
    $this->assertSame('external', $group['type']);
    $this->assertSame('all', $group['media']);
    $this->assertTrue($group['preprocess']);
    $this->assertCount(1, $group['items']);
    $this->assertContainsEquals($css_assets['external.css'], $group['items']);

    // Check group 4.
    $group = $groups[2];
    $this->assertSame('file', $group['type']);
    $this->assertSame('all', $group['media']);
    $this->assertTrue($group['preprocess']);
    $this->assertCount(1, $group['items']);
    $this->assertContainsEquals($css_assets['elements.css'], $group['items']);

    // Check group 5.
    $group = $groups[3];
    $this->assertSame('file', $group['type']);
    $this->assertSame('print', $group['media']);
    $this->assertTrue($group['preprocess']);
    $this->assertCount(1, $group['items']);
    $this->assertContainsEquals($css_assets['print.css'], $group['items']);
  }

  /**
   * Tests \Drupal\Core\Asset\CssCollectionGrouper.
   */
  public function testGrouperWithAggregateTargets(): void {
    $css_assets = [
      'a.css' => [
        'group' => -100,
        'category' => 'base',
        'type' => 'file',
        'weight' => 0,
        'media' => 'all',
        'preprocess' => TRUE,
        'aggregate_target' => ['css' => TRUE],
        'data' => 'a.css',
        'basename' => 'a.css',
        'library' => 'base',
      ],
      'b.css' => [
        'group' => -100,
        'category' => 'base',
        'type' => 'file',
        'weight' => 0.05,
        'media' => 'all',
        'preprocess' => TRUE,
        'aggregate_target' => ['css' => TRUE],
        'data' => 'b.css',
        'basename' => 'c.css',
        'library' => 'base',
      ],
      'c.css' => [
        'group' => -100,
        'category' => 'base',
        'type' => 'file',
        'weight' => 0.1,
        'media' => 'all',
        'preprocess' => TRUE,
        'aggregate_target' => ['css' => TRUE],
        'data' => 'c.css',
        'basename' => 'c.css',
        'library' => 'base',
      ],
    ];

    $groups = $this->grouper->group($css_assets);

    // When all files are sequentially in the same library, the resultant group
    // should have the 'libraries' key set.
    $this->assertArrayHasKey('libraries', $groups[0]);
    $this->assertCount(1, $groups);

    // Now change b.css to be in a separate library. Because its position
    // is in-between a.css and c.css it will force a fallback in the
    // aggregate_target logic, resulting in three groups, and only b.css
    // having the 'libraries' key.
    $css_assets['b.css']['library'] = 'different';
    $groups = $this->grouper->group($css_assets);
    $this->assertArrayNotHasKey('libraries', $groups[0]);
    $this->assertArrayHasKey('libraries', $groups[1]);
    $this->assertArrayNotHasKey('libraries', $groups[2]);
    $this->assertCount(3, $groups);

    // Now set aggregate_target FALSE for b.css.
    $css_assets['b.css']['aggregate_target']['css'] = FALSE;
    $groups = $this->grouper->group($css_assets);
    $this->assertArrayNotHasKey('libraries', $groups[0]);
    $this->assertArrayNotHasKey('libraries', $groups[1]);
    $this->assertArrayNotHasKey('libraries', $groups[2]);
    $this->assertCount(3, $groups);

    // With aggregate_target FALSE for all three files, there should be one
    // group with no libraries key.
    $css_assets['a.css']['aggregate_target']['css'] = FALSE;
    $css_assets['c.css']['aggregate_target']['css'] = FALSE;
    $groups = $this->grouper->group($css_assets);
    $this->assertArrayNotHasKey('libraries', $groups[0]);
    $this->assertCount(1, $groups);

    // When the libraries match but category is different,
    // then aggregates should be split by category.
    $css_assets['a.css']['aggregate_target']['css'] = TRUE;
    $css_assets['b.css']['aggregate_target']['css'] = TRUE;
    $css_assets['b.css']['library'] = 'base';
    $css_assets['c.css']['aggregate_target']['css'] = TRUE;
    $css_assets['c.css']['category'] = 'different';
    $groups = $this->grouper->group($css_assets);
    $this->assertArrayHasKey('libraries', $groups[0]);
    $this->assertArrayHasKey('libraries', $groups[1]);
    $this->assertCount(2, $groups);
  }

}
