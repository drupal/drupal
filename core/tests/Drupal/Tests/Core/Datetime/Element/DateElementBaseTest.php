<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Datetime\Element;

use Drupal\Core\Datetime\Element\DateElementBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the DateElementBase class.
 */
#[Group('Datetime')]
class DateElementBaseTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $language = $this->createStub(LanguageInterface::class);
    $language->method('getId')->willReturn('en');

    $language_manager = $this->createStub(LanguageManagerInterface::class);
    $language_manager->method('getCurrentLanguage')->willReturn($language);

    $container = new ContainerBuilder();
    $container->set('language_manager', $language_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Tests datetimeRangeYears() with various year range strings.
   *
   * @param string $range
   *   The year range string to test.
   * @param int $expected_min
   *   The expected minimum year.
   * @param int $expected_max
   *   The expected maximum year.
   */
  #[DataProvider('providerTestDatetimeRangeYears')]
  public function testDatetimeRangeYears(
    string $range,
    int $expected_min,
    int $expected_max,
  ): void {
    $method = new \ReflectionMethod(DateElementBase::class, 'datetimeRangeYears');
    /** @var array{string, string} $result */
    $result = $method->invoke(NULL, $range);
    [$min, $max] = $result;
    $this->assertSame($expected_min, (int) $min);
    $this->assertSame($expected_max, (int) $max);
  }

  /**
   * Data provider for testDatetimeRangeYears().
   */
  public static function providerTestDatetimeRangeYears(): array {
    $this_year = (int) date('Y');
    return [
      'standard 4-digit range' => ['1900:2050', 1900, 2050],
      'year less than 1000' => ['500:1500', 500, 1500],
      'year zero as min' => ['0:2050', 0, 2050],
      'both years less than 1000' => ['0:999', 0, 999],
      'single digit year' => ['1:9999', 1, 9999],
      'relative max' => ['1900:+0', 1900, $this_year],
      'year zero with relative max' => ['0:+50', 0, $this_year + 50],
      'both relative' => ['-5:+5', $this_year - 5, $this_year + 5],
      'both year zero' => ['0:0', 0, 0],
      'both this year (+0)' => ['+0:+0', $this_year, $this_year],
      'both this year (-0)' => ['-0:-0', $this_year, $this_year],
      'negative four digit year' => ['-1000:-0', $this_year - 1000, $this_year],
      'negative three digit year' => ['-100:-1', $this_year - 100, $this_year - 1],
      'negative two digit year' => ['-10:-1', $this_year - 10, $this_year - 1],
    ];
  }

}
