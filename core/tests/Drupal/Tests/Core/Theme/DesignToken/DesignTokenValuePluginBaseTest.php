<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\DesignToken;

use Drupal\Core\Theme\DesignToken\DesignTokenValuePluginBase;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests design token value base methods.
 */
#[CoversClass(DesignTokenValuePluginBase::class)]
#[Group('design_token')]
class DesignTokenValuePluginBaseTest extends UnitTestCase {

  /**
   * Tests prepareConfiguration.
   */
  #[DataProvider('providerPrepareConfiguration')]
  public function testPrepareConfiguration(mixed $configuration, array $expected): void {
    $this->assertEquals($expected, DesignTokenValuePluginBase::prepareConfiguration($configuration));
  }

  /**
   * Data provider for ::testPrepareConfiguration().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerPrepareConfiguration(): iterable {
    yield 'String' => [
      'my value',
      [
        'value' => 'my value',
      ],
    ];

    yield 'Array: list' => [
      [
        'my value',
        'my value 2',
      ],
      [
        'value' => [
          'my value',
          'my value 2',
        ],
      ],
    ];

    yield 'Array: associative' => [
      [
        'unit' => 'rem',
      ],
      [
        'unit' => 'rem',
      ],
    ];
  }

}
