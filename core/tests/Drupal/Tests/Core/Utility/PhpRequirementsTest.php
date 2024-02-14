<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Utility;

use Drupal\Core\Utility\PhpRequirements;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the \Drupal\Core\Utility\PhpRequirements class.
 *
 * @coversDefaultClass \Drupal\Core\Utility\PhpRequirements
 * @group Utility
 */
class PhpRequirementsTest extends UnitTestCase {

  /**
   * Ensures that PHP EOL dates are valid.
   *
   * This ensures that that all of the PHP EOL Date items are valid ISO 8601
   * dates and are keyed by a valid version number.
   */
  public function testPhpEolDates(): void {
    $reflected = new \ReflectionClass(PhpRequirements::class);
    $php_eol_dates = $reflected->getStaticPropertyValue('phpEolDates');

    foreach ($php_eol_dates as $version => $eol_date) {
      // Ensure that all of the version numbers are defined in a superset of
      // semver: 'major.minor.patch-modifier', where (unlike in semver) all
      // parts but the major are optional.
      // @see version_compare()
      $this->assertMatchesRegularExpression('/^([0-9]+)(\.([0-9]+)(\.([0-9]+)(-[A-Za-z0-9]+)?)?)?$/', $version);

      // Ensure that all of the EOL dates are defined using ISO 8601 format.
      $this->assertMatchesRegularExpression('/^([0-9]{4})-(1[0-2]|0[1-9])-(3[01]|0[1-9]|[12][0-9])$/', $eol_date);
    }

    // Ensure that the EOL list is sorted in an ascending order by the date. If
    // there are multiple versions EOL on the same day, sort by the PHP
    // version.
    uksort($php_eol_dates, function ($a, $b) use ($php_eol_dates) {
      $a_date = strtotime($php_eol_dates[$a]);
      $b_date = strtotime($php_eol_dates[$b]);
      if ($a_date === $b_date) {
        return $a <=> $b;
      }
      return $a_date <=> $b_date;
    });
    $this->assertSame($php_eol_dates, $reflected->getStaticPropertyValue('phpEolDates'));
  }

  /**
   * Tests the minimum supported PHP for valid scenarios.
   *
   * @param string $date_string
   *   A valid PHP date string for the date to check.
   * @param string $drupal_minimum_php
   *   The PHP minimum version hard requirement for the Drupal version, below
   *   which Drupal cannot be installed or updated, typically
   *   \Drupal::MINIMUM_PHP.
   * @param string[] $php_eol_dates
   *   Associative array of PHP version EOL date strings, keyed by the PHP minor
   *   version.
   * @param string $expected_php_version
   *   The PHP version the test should recommend.
   *
   * @covers ::getMinimumSupportedPhp
   *
   * @dataProvider providerMinimumSupportedPhp
   */
  public function testMinimumSupportedPhp(string $date_string, string $drupal_minimum_php, array $php_eol_dates, string $expected_php_version): void {
    $reflected = new \ReflectionClass(PhpRequirements::class);
    $reflected->setStaticPropertyValue('drupalMinimumPhp', $drupal_minimum_php);
    $reflected->setStaticPropertyValue('phpEolDates', $php_eol_dates);
    $date = new \DateTime($date_string);
    $this->assertSame($expected_php_version, PhpRequirements::getMinimumSupportedPhp($date));
  }

  /**
   * Data provider for ::testMinimumSupportedPhp().
   *
   * See the parameter documentation of testMinimumSupportedPhp() for the test
   * array structure. The last element is the expected minimum supported PHP.
   *
   * @return \Generator
   *   Test scenarios.
   */
  public static function providerMinimumSupportedPhp(): \Generator {
    $eol_lists = [];

    // Test against the known valid data from 9.0.0 to 9.3.0.
    $eol_lists['d9_release'] = [
      '7.2' => '2020-11-30',
      '7.3' => '2021-12-06',
      '7.4' => '2022-11-28',
      '8.0' => '2023-11-26',
      '8.1' => '2024-11-25',
    ];

    // The actual situation the day of 9.0.0's release.
    yield ['2020-06-03', '7.3.0', $eol_lists['d9_release'], '7.3.0'];

    // If Drupal's MINIMUM_PHP had been 7.3.12 then.
    yield ['2020-06-03', '7.3.12', $eol_lists['d9_release'], '7.3.12'];

    // If Drupal's MINIMUM_PHP had been 7.2.17 then.
    yield ['2020-06-03', '7.2.17', $eol_lists['d9_release'], '7.2.17'];

    // If Drupal's MINIMUM_PHP had been 7.1.5 then.
    yield ['2020-06-03', '7.1.5', $eol_lists['d9_release'], '7.2'];

    // If the PHP EOL date list were empty.
    yield ['2020-06-03', '7.3.0', [], '7.3.0'];

    // Cases around PHP 7.2's EOL.
    yield ['2020-11-29', '7.3.0', $eol_lists['d9_release'], '7.3.0'];
    yield ['2020-11-30', '7.3.0', $eol_lists['d9_release'], '7.3.0'];
    yield ['2020-12-01', '7.3.0', $eol_lists['d9_release'], '7.3.0'];

    // Cases around PHP 7.3's EOL.
    yield ['2021-12-05', '7.3.0', $eol_lists['d9_release'], '7.3.0'];
    yield ['2021-12-06', '7.3.0', $eol_lists['d9_release'], '7.4'];
    yield ['2021-12-07', '7.3.0', $eol_lists['d9_release'], '7.4'];

    // Cases around PHP 7.4's EOL.
    yield ['2022-11-27', '7.3.0', $eol_lists['d9_release'], '7.4'];
    yield ['2022-11-28', '7.3.0', $eol_lists['d9_release'], '8.0'];
    yield ['2022-11-29', '7.3.0', $eol_lists['d9_release'], '8.0'];

    // Cases around PHP 8.0's EOL.
    yield ['2023-11-25', '7.3.0', $eol_lists['d9_release'], '8.0'];
    yield ['2023-11-26', '7.3.0', $eol_lists['d9_release'], '8.1'];
    yield ['2023-11-27', '7.3.0', $eol_lists['d9_release'], '8.1'];

    // Cases around PHP 8.1's EOL, without any data for 8.2.
    yield ['2024-11-24', '7.3.0', $eol_lists['d9_release'], '8.1'];
    yield ['2024-11-25', '7.3.0', $eol_lists['d9_release'], '8.1'];
    yield ['2024-11-26', '7.3.0', $eol_lists['d9_release'], '8.1'];

    // Cases for Drupal 10, with its current 8.0.2 MINIMUM_PHP, prior to PHP
    // 8.0's EOL.
    yield ['2021-12-05', '8.0.2', $eol_lists['d9_release'], '8.0.2'];
    yield ['2021-12-06', '8.0.2', $eol_lists['d9_release'], '8.0.2'];
    yield ['2021-12-07', '8.0.2', $eol_lists['d9_release'], '8.0.2'];
    yield ['2022-11-27', '8.0.2', $eol_lists['d9_release'], '8.0.2'];
    yield ['2022-11-28', '8.0.2', $eol_lists['d9_release'], '8.0.2'];
    yield ['2022-11-29', '8.0.2', $eol_lists['d9_release'], '8.0.2'];

    // Cases for Drupal 10 around PHP 8.0's EOL.
    yield ['2023-11-25', '8.0.2', $eol_lists['d9_release'], '8.0.2'];
    yield ['2023-11-26', '8.0.2', $eol_lists['d9_release'], '8.1'];
    yield ['2023-11-27', '8.0.2', $eol_lists['d9_release'], '8.1'];

    // Cases for Drupal 10 around and after PHP 8.1's EOL, without any data
    // for 8.2.
    yield ['2024-11-24', '8.0.2', $eol_lists['d9_release'], '8.1'];
    yield ['2024-11-25', '8.0.2', $eol_lists['d9_release'], '8.1'];
    yield ['2024-11-26', '8.0.2', $eol_lists['d9_release'], '8.1'];
    yield ['2027-01-01', '8.0.2', $eol_lists['d9_release'], '8.1'];

    // Test against a hypothetical set of PHP versions that have an LTS
    // (supported longer than subsequent versions).
    $eol_lists['php_with_lts'] = $eol_lists['d9_release'];

    // Ensure that the PHP version with longest support is listed last.
    unset($eol_lists['php_with_lts']['7.4']);
    $eol_lists['php_with_lts']['7.4'] = '2025-11-28';

    yield ['2021-12-05', '7.3', $eol_lists['php_with_lts'], '7.3'];
    yield ['2021-12-06', '7.3', $eol_lists['php_with_lts'], '7.4'];
    yield ['2022-11-28', '7.3', $eol_lists['php_with_lts'], '7.4'];
    yield ['2023-11-26', '7.3', $eol_lists['php_with_lts'], '7.4'];
    yield ['2024-11-25', '7.3', $eol_lists['php_with_lts'], '7.4'];
    yield ['2025-12-01', '7.3', $eol_lists['php_with_lts'], '7.4'];

    // Case with multiple versions EOL on the same day.
    $eol_lists['same_eol_date'] = $eol_lists['d9_release'];
    $eol_lists['same_eol_date']['8.2'] = $eol_lists['same_eol_date']['8.1'];

    yield ['2021-12-05', '7.3', $eol_lists['same_eol_date'], '7.3'];
    yield ['2023-11-27', '8.0.2', $eol_lists['same_eol_date'], '8.1'];
    yield ['2027-07-31', '8.0.2', $eol_lists['same_eol_date'], '8.2'];
  }

}
