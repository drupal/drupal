<?php

declare(strict_types=1);

namespace Drupal\Tests\stable9\Unit;

use Drupal\stable9\Hook\Stable9Hooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests backward compatibility for block templates.
 */
#[Group('Theme')]
class BlockBackwardCompatibilityTest extends UnitTestCase {

  /**
   * Tests copying content attributes to the wrapper.
   *
   * @legacy-covers \Drupal\stable9\Hook\Stable9Hooks::preprocessBlock()
   */
  public function testAttributeCopy(): void {
    $variables = [
      'attributes' => [
        'id' => 'test-block',
        'class' => ['wrapper-class'],
      ],
      'content' => [
        '#attributes' => [
          'class' => ['content-class'],
          'data-foo' => 'bar',
        ],
      ],
    ];
    $hooks = new Stable9Hooks();
    $hooks->preprocessBlock($variables);

    $expected = [
      'attributes' => [
        'id' => 'test-block',
        'class' => [
          'wrapper-class',
          'content-class',
        ],
        'data-foo' => 'bar',
      ],
      'content' => [],
    ];
    $this->assertEquals($expected, $variables);
  }

}
