<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Test\Comparator;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\TestTools\Comparator\MarkupInterfaceComparator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Comparator\Factory;

/**
 * Tests \Drupal\TestTools\Comparator\MarkupInterfaceComparator.
 *
 * We need to test the class with a kernel test since casting MarkupInterface
 * objects to strings can require an initialized container.
 */
#[CoversClass(MarkupInterfaceComparator::class)]
#[Group('Test')]
#[Group('#slow')]
#[RunTestsInSeparateProcesses]
class MarkupInterfaceComparatorTest extends KernelTestBase {

  /**
   * The markup comparator.
   *
   * @var \Drupal\TestTools\Comparator\MarkupInterfaceComparator
   */
  protected $comparator;

  /**
   * The comparator factory.
   *
   * @var \SebastianBergmann\Comparator\Factory
   */
  protected $factory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->factory = new Factory();
    $this->comparator = new MarkupInterfaceComparator();
    $this->comparator->setFactory($this->factory);
  }

  /**
   * Provides test data for the comparator.
   *
   * @return array
   *   Each array entry has:
   *   - test expected value,
   *   - test actual value,
   *   - a bool indicating the expected return value of ::accepts,
   *   - a value indicating the expected result of ::assertEquals, TRUE if
   *     comparison should match, FALSE if error, or a class name of an object
   *     thrown.
   */
  public static function dataSetProvider(): array {
    return [
      'FormattableMarkup vs FormattableMarkup, equal' => [
        new FormattableMarkup('GoldFinger', []),
        new FormattableMarkup('GoldFinger', []),
        TRUE,
        TRUE,
      ],
      'FormattableMarkup vs FormattableMarkup, not equal' => [
        new FormattableMarkup('GoldFinger', []),
        new FormattableMarkup('moonraker', []),
        TRUE,
        ComparisonFailure::class,
      ],
      'FormattableMarkup vs string, equal' => [
        new FormattableMarkup('GoldFinger', []),
        'GoldFinger',
        TRUE,
        TRUE,
      ],
      'string vs FormattableMarkup, equal' => [
        'GoldFinger',
        new FormattableMarkup('GoldFinger', []),
        TRUE,
        TRUE,
      ],
      'TranslatableMarkup vs FormattableMarkup, equal' => [
        new TranslatableMarkup('GoldFinger'),
        new FormattableMarkup('GoldFinger', []),
        TRUE,
        TRUE,
      ],
      'TranslatableMarkup vs string, not equal' => [
        new TranslatableMarkup('GoldFinger'),
        'moonraker',
        TRUE,
        ComparisonFailure::class,
      ],
      'TranslatableMarkup vs int, equal' => [
        new TranslatableMarkup('1234'),
        1234,
        TRUE,
        TRUE,
      ],
      'int vs TranslatableMarkup, equal' => [
        1234,
        new TranslatableMarkup('1234'),
        TRUE,
        TRUE,
      ],
      'FormattableMarkup vs array' => [
        new FormattableMarkup('GoldFinger', []),
        ['GoldFinger'],
        FALSE,
        \InvalidArgumentException::class,
      ],
      'stdClass vs TranslatableMarkup' => [
        (object) ['GoldFinger'],
        new TranslatableMarkup('GoldFinger'),
        FALSE,
        FALSE,
      ],
      'string vs string, equal' => [
        'GoldFinger',
        'GoldFinger',
        FALSE,
        \LogicException::class,
      ],
      'html string with tags vs html string with tags, equal' => [
        '<em class="placeholder">For Your Eyes</em> Only',
        '<em class="placeholder">For Your Eyes</em> Only',
        FALSE,
        \LogicException::class,
      ],
      'html string with tags vs plain string, not equal' => [
        '<em class="placeholder">For Your Eyes</em> Only',
        'For Your Eyes Only',
        FALSE,
        FALSE,
      ],
      'FormattableMarkup with placeholders vs FormattableMarkup with placeholders, equal' => [
        new FormattableMarkup('%placeholder Only', ['%placeholder' => 'For Your Eyes']),
        new FormattableMarkup('%placeholder Only', ['%placeholder' => 'For Your Eyes']),
        TRUE,
        TRUE,
      ],
      'FormattableMarkup with placeholders vs FormattableMarkup with placeholders, not equal' => [
        new FormattableMarkup('%placeholder Only', ['%placeholder' => 'For Your Eyes']),
        new FormattableMarkup('%placeholder Too', ['%placeholder' => 'For Your Eyes']),
        TRUE,
        FALSE,
      ],
      'html string with tags vs FormattableMarkup, equal' => [
        '<em class="placeholder">For Your Eyes</em> Only',
        new FormattableMarkup('%placeholder Only', ['%placeholder' => 'For Your Eyes']),
        TRUE,
        \RuntimeException::class,
      ],
      'html string with tags vs FormattableMarkup, not equal' => [
        '<em class="placeholder">For Your Eyes</em> Too',
        new FormattableMarkup('%placeholder Only', ['%placeholder' => 'For Your Eyes']),
        TRUE,
        \RuntimeException::class,
      ],
      'FormattableMarkup vs html string with tags, equal' => [
        new FormattableMarkup('%placeholder Only', ['%placeholder' => 'For Your Eyes']),
        '<em class="placeholder">For Your Eyes</em> Only',
        TRUE,
        \RuntimeException::class,
      ],
      'FormattableMarkup vs html string with tags, not equal' => [
        new FormattableMarkup('%placeholder Only', ['%placeholder' => 'For Your Eyes']),
        '<em class="placeholder">For Your Eyes</em> Too',
        TRUE,
        \RuntimeException::class,
      ],
    ];
  }

  /**
   * Tests accepts.
   *
   * @legacy-covers ::accepts
   */
  #[DataProvider('dataSetProvider')]
  public function testAccepts($expected, $actual, bool $accepts_result, $equals_result): void {
    if ($accepts_result) {
      $this->assertTrue($this->comparator->accepts($expected, $actual));
    }
    else {
      $this->assertFalse($this->comparator->accepts($expected, $actual));
    }
  }

  /**
   * Tests assert equals.
   *
   * @legacy-covers ::assertEquals
   */
  #[DataProvider('dataSetProvider')]
  public function testAssertEquals($expected, $actual, bool $accepts_result, $equals_result): void {
    try {
      $this->assertNull($this->comparator->assertEquals($expected, $actual));
      $this->assertTrue($equals_result);
    }
    catch (\Throwable $e) {
      if ($equals_result === FALSE) {
        $this->assertNotNull($e->getMessage());
      }
      else {
        $this->assertInstanceOf($equals_result, $e);
      }
    }
  }

}
