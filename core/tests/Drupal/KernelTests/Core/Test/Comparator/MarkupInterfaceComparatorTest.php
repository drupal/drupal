<?php

namespace Drupal\KernelTests\Core\Test\Comparator;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\TestTools\Comparator\MarkupInterfaceComparator;
use PHPUnit\Framework\Error\Warning;
use SebastianBergmann\Comparator\Factory;
use SebastianBergmann\Comparator\ComparisonFailure;

/**
 * Tests \Drupal\TestTools\Comparator\MarkupInterfaceComparator.
 *
 * We need to test the class with a kernel test since casting MarkupInterface
 * objects to strings can require an initialized container.
 *
 * @group Test
 *
 * @coversDefaultClass \Drupal\TestTools\Comparator\MarkupInterfaceComparator
 */
class MarkupInterfaceComparatorTest extends KernelTestBase {

  /**
   * @var \Drupal\TestTools\Comparator\MarkupInterfaceComparator
   */
  protected $comparator;

  /**
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
  public function dataSetProvider() {
    return [
      'FormattableMarkup vs FormattableMarkup, equal' => [
        new FormattableMarkup('goldfinger', []),
        new FormattableMarkup('goldfinger', []),
        TRUE,
        TRUE,
      ],
      'FormattableMarkup vs FormattableMarkup, not equal' => [
        new FormattableMarkup('goldfinger', []),
        new FormattableMarkup('moonraker', []),
        TRUE,
        ComparisonFailure::class,
      ],
      'FormattableMarkup vs string, equal' => [
        new FormattableMarkup('goldfinger', []),
        'goldfinger',
        TRUE,
        TRUE,
      ],
      'string vs FormattableMarkup, equal' => [
        'goldfinger',
        new FormattableMarkup('goldfinger', []),
        TRUE,
        TRUE,
      ],
      'TranslatableMarkup vs FormattableMarkup, equal' => [
        new TranslatableMarkup('goldfinger'),
        new FormattableMarkup('goldfinger', []),
        TRUE,
        TRUE,
      ],
      'TranslatableMarkup vs string, not equal' => [
        new TranslatableMarkup('goldfinger'),
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
        new FormattableMarkup('goldfinger', []),
        ['goldfinger'],
        FALSE,
        Warning::class,
      ],
      'stdClass vs TranslatableMarkup' => [
        (object) ['goldfinger'],
        new TranslatableMarkup('goldfinger'),
        FALSE,
        FALSE,
      ],
      'string vs string, equal' => [
        'goldfinger',
        'goldfinger',
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
    ];
  }

  /**
   * Provides test data for the comparator - deprecated cases.
   *
   * @return array
   *   Each array entry has:
   *   - test expected value,
   *   - test actual value,
   *   - a bool indicating the expected return value of ::accepts,
   *   - a value indicating the expected result of ::assertEquals, TRUE if
   *     comparison should match, FALSE if error, or a class name of an object
   *     thrown.
   *   - the expected deprecation message.
   */
  public function dataSetProviderDeprecatedCases() {
    return [
      'html string with tags vs FormattableMarkup, equal' => [
        '<em class="placeholder">For Your Eyes</em> Only',
        new FormattableMarkup('%placeholder Only', ['%placeholder' => 'For Your Eyes']),
        TRUE,
        TRUE,
        'Using assert[Not]Equals() to compare markup between MarkupInterface objects and plain strings is deprecated in drupal:10.1.0 and will throw an error from drupal:11.0.0. Expected: \'<em class="placeholder">For Your Eyes</em> Only\' - Actual \'<em class="placeholder">For Your Eyes</em> Only\'. Use assert[Not]Same() and cast objects to string instead. See https://www.drupal.org/node/3334057',
      ],
      'html string with tags vs FormattableMarkup, not equal' => [
        '<em class="placeholder">For Your Eyes</em> Too',
        new FormattableMarkup('%placeholder Only', ['%placeholder' => 'For Your Eyes']),
        TRUE,
        FALSE,
        'Using assert[Not]Equals() to compare markup between MarkupInterface objects and plain strings is deprecated in drupal:10.1.0 and will throw an error from drupal:11.0.0. Expected: \'<em class="placeholder">For Your Eyes</em> Too\' - Actual \'<em class="placeholder">For Your Eyes</em> Only\'. Use assert[Not]Same() and cast objects to string instead. See https://www.drupal.org/node/3334057',
      ],
      'FormattableMarkup vs html string with tags, equal' => [
        new FormattableMarkup('%placeholder Only', ['%placeholder' => 'For Your Eyes']),
        '<em class="placeholder">For Your Eyes</em> Only',
        TRUE,
        TRUE,
        'Using assert[Not]Equals() to compare markup between MarkupInterface objects and plain strings is deprecated in drupal:10.1.0 and will throw an error from drupal:11.0.0. Expected: \'<em class="placeholder">For Your Eyes</em> Only\' - Actual \'<em class="placeholder">For Your Eyes</em> Only\'. Use assert[Not]Same() and cast objects to string instead. See https://www.drupal.org/node/3334057',
      ],
      'FormattableMarkup vs html string with tags, not equal' => [
        new FormattableMarkup('%placeholder Only', ['%placeholder' => 'For Your Eyes']),
        '<em class="placeholder">For Your Eyes</em> Too',
        TRUE,
        FALSE,
        'Using assert[Not]Equals() to compare markup between MarkupInterface objects and plain strings is deprecated in drupal:10.1.0 and will throw an error from drupal:11.0.0. Expected: \'<em class="placeholder">For Your Eyes</em> Only\' - Actual \'<em class="placeholder">For Your Eyes</em> Too\'. Use assert[Not]Same() and cast objects to string instead. See https://www.drupal.org/node/3334057',
      ],
    ];
  }

  /**
   * @covers ::accepts
   * @dataProvider dataSetProvider
   */
  public function testAccepts($expected, $actual, bool $accepts_result, $equals_result): void {
    if ($accepts_result) {
      $this->assertTrue($this->comparator->accepts($expected, $actual));
    }
    else {
      $this->assertFalse($this->comparator->accepts($expected, $actual));
    }
  }

  /**
   * @covers ::assertEquals
   * @dataProvider dataSetProvider
   */
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

  /**
   * @covers ::assertEquals
   * @dataProvider dataSetProviderDeprecatedCases
   * @group legacy
   */
  public function testDeprecatedAssertEquals($expected, $actual, bool $accepts_result, $equals_result, string $deprecation_message): void {
    if ($accepts_result) {
      $this->assertTrue($this->comparator->accepts($expected, $actual));
    }
    else {
      $this->assertFalse($this->comparator->accepts($expected, $actual));
    }
    if ($deprecation_message) {
      $this->expectDeprecation($deprecation_message);
    }
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
