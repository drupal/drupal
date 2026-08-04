<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Asset;

use Drupal\Core\Asset\JsCollectionGrouper;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the CSS asset collection grouper.
 */
#[Group('Asset')]
class JsCollectionGrouperUnitTest extends UnitTestCase {

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

    $this->grouper = new JsCollectionGrouper();
  }

  /**
   * Tests \Drupal\Core\Asset\CssCollectionGrouper.
   */
  public function testGrouperWithAggregateTargets(): void {
    $js_assets = [
      'a.js' => [
        'group' => -100,
        'type' => 'file',
        'weight' => 0,
        'preprocess' => TRUE,
        'aggregate_target' => ['js' => TRUE],
        'data' => 'a.js',
        'basename' => 'a.js',
        'library' => 'base',
      ],
      'b.js' => [
        'group' => -100,
        'type' => 'file',
        'weight' => 0.05,
        'preprocess' => TRUE,
        'aggregate_target' => ['js' => TRUE],
        'data' => 'b.js',
        'basename' => 'c.js',
        'library' => 'base',
      ],
      'c.js' => [
        'group' => -100,
        'type' => 'file',
        'weight' => 0.1,
        'preprocess' => TRUE,
        'aggregate_target' => ['js' => TRUE],
        'data' => 'c.js',
        'basename' => 'c.js',
        'library' => 'base',
      ],
    ];

    $groups = $this->grouper->group($js_assets);

    // When all files are sequentially in the same library, the resultant group
    // should have the 'libraries' key set.
    $this->assertArrayHasKey('libraries', $groups[0]);
    $this->assertCount(1, $groups);

    // Now change b.js to be in a separate library. Because its position
    // is in-between a.js and c.js it will force a fallback in the
    // aggregate_target logic, resulting in three groups, and only b.js
    // having the 'libraries' key.
    $js_assets['b.js']['library'] = 'different';
    $groups = $this->grouper->group($js_assets);
    $this->assertArrayNotHasKey('libraries', $groups[0]);
    $this->assertArrayHasKey('libraries', $groups[1]);
    $this->assertArrayNotHasKey('libraries', $groups[2]);
    $this->assertCount(3, $groups);

    // Now set aggregate_target FALSE for b.js.
    $js_assets['b.js']['aggregate_target']['js'] = FALSE;
    $groups = $this->grouper->group($js_assets);
    $this->assertArrayNotHasKey('libraries', $groups[0]);

    // Even though the second asset doesn't specify an aggregate target,
    // the library is self contained in a single asset group so the libraries
    // key can be used.
    $this->assertArrayHasKey('libraries', $groups[1]);
    $this->assertArrayNotHasKey('libraries', $groups[2]);
    $this->assertCount(3, $groups);

    // With aggregate_target FALSE for all three files, there should be one
    // group. The libraries key is added even though there was no aggregate
    // target because all of the libraries were encapsulated within the asset
    // group.
    $js_assets['a.js']['aggregate_target']['js'] = FALSE;
    $js_assets['c.js']['aggregate_target']['js'] = FALSE;
    $groups = $this->grouper->group($js_assets);
    $this->assertArrayHasKey('libraries', $groups[0]);
    $this->assertCount(1, $groups);
  }

}
