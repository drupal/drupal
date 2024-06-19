<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\DeprecationHelper;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Utility\DeprecationHelper
 * @group Utility
 */
class DeprecationHelperTest extends TestCase {

  /**
   * @param string $currentVersion
   *   The core version to test against.
   * @param array $tests
   *   Array of versions and their expected result.
   *
   * @dataProvider deprecatedHelperTestCases
   */
  public function testDeprecationHelper(string $currentVersion, array $tests): void {
    foreach ($tests as $deprecatedVersion => $expectedCallable) {
      $result = DeprecationHelper::backwardsCompatibleCall(
        $currentVersion,
        $deprecatedVersion,
        fn() => 'current',
        fn() => 'deprecated',
      );
      $this->assertEquals($expectedCallable, $result, "Change introduced in $deprecatedVersion should return $expectedCallable for core version $currentVersion");
    }
  }

  public static function deprecatedHelperTestCases(): array {
    return [
      [
        'currentVersion' => '10.2.x-dev',
        'tests' => [
          '11.0.0' => 'deprecated',
          '10.3.0' => 'deprecated',
          '10.2.1' => 'deprecated',
          '10.2.0' => 'current',
          '10.1.0' => 'current',
          '10.0.0' => 'current',
          '9.5.0' => 'current',
        ],
      ],
      [
        'currentVersion' => '10.2.1',
        'tests' => [
          '11.0.0' => 'deprecated',
          '10.2.2' => 'deprecated',
          '10.2.1' => 'current',
          '10.2.0' => 'current',
          '10.1.0' => 'current',
          '10.0.0' => 'current',
          '9.5.0' => 'current',
        ],
      ],
      [
        'currentVersion' => '11.0-dev',
        'tests' => [
          '11.5.0' => 'deprecated',
          '11.0.1' => 'deprecated',
          '11.0.0' => 'current',
          '10.1.0' => 'current',
          '9.5.0' => 'current',
        ],
      ],
      [
        'currentVersion' => '11.0.0',
        'tests' => [
          '11.5.0' => 'deprecated',
          '11.2.1' => 'deprecated',
          '11.0.1' => 'deprecated',
          '11.0.0' => 'current',
          '10.1.0' => 'current',
          '9.5.0' => 'current',
        ],
      ],
    ];
  }

}
