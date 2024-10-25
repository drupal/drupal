<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * @covers \Drupal\package_manager\Event\RequireEventTrait
 * @group package_manager
 * @internal
 */
class RequireEventTraitTest extends UnitTestCase {

  /**
   * Tests that runtime and dev packages are keyed correctly.
   *
   * @param string[] $runtime_packages
   *   The runtime package constraints passed to the event constructor.
   * @param string[] $dev_packages
   *   The dev package constraints passed to the event constructor.
   * @param string[] $expected_runtime_packages
   *   The keyed runtime packages that should be returned by
   *   ::getRuntimePackages().
   * @param string[] $expected_dev_packages
   *   The keyed dev packages that should be returned by ::getDevPackages().
   *
   * @dataProvider providerGetPackages
   */
  public function testGetPackages(array $runtime_packages, array $dev_packages, array $expected_runtime_packages, array $expected_dev_packages): void {
    $stage = $this->createMock('\Drupal\package_manager\StageBase');

    $events = [
      '\Drupal\package_manager\Event\PostRequireEvent',
      '\Drupal\package_manager\Event\PreRequireEvent',
    ];
    foreach ($events as $event) {
      /** @var \Drupal\package_manager\Event\RequireEventTrait $event */
      $event = new $event($stage, $runtime_packages, $dev_packages);
      $this->assertSame($expected_runtime_packages, $event->getRuntimePackages());
      $this->assertSame($expected_dev_packages, $event->getDevPackages());
    }
  }

  /**
   * Data provider for testGetPackages().
   *
   * @return mixed[]
   *   The test cases.
   */
  public static function providerGetPackages(): array {
    return [
      'Package with constraint' => [
        ['drupal/new_package:^8.1'],
        ['drupal/dev_package:^9'],
        ['drupal/new_package' => '^8.1'],
        ['drupal/dev_package' => '^9'],
      ],
      'Package without constraint' => [
        ['drupal/new_package'],
        ['drupal/dev_package'],
        ['drupal/new_package' => '*'],
        ['drupal/dev_package' => '*'],
      ],
    ];
  }

}
