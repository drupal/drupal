<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Locale;

use Drupal\Core\Locale\CountryManager;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Country Manager functionality.
 */
#[Group('CountryManager')]
#[CoversClass(CountryManager::class)]
#[RunTestsInSeparateProcesses]
class CountryManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'locale_test',
  ];

  /**
   * Tests that hook_countries_alters() works as expected.
   */
  public function testHookCountriesAlter(): void {
    $countries = $this->container->get('country_manager')->getList();
    self::assertArrayHasKey('EB', $countries);
    self::assertSame('Elbonia', $countries['EB']);
  }

}
