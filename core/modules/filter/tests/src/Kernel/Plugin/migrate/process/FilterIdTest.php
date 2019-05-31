<?php

namespace Drupal\Tests\filter\Kernel\Plugin\migrate\process;

use Drupal\filter\Plugin\migrate\process\FilterID;
use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Unit tests of the filter_id plugin.
 *
 * @coversDefaultClass \Drupal\filter\Plugin\migrate\process\FilterID
 * @group filter
 */
class FilterIdTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['filter'];

  /**
   * The mocked MigrateExecutable.
   *
   * @var \Drupal\migrate\MigrateExecutableInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $executable;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executable = $this->createMock(MigrateExecutableInterface::class);
  }

  /**
   * Tests transformation of filter_id plugin.
   *
   * @param mixed $value
   *   The input value to the plugin.
   * @param string $expected_value
   *   The output value expected from the plugin.
   * @param string $invalid_id
   *   (optional) The invalid plugin ID which is expected to be logged by the
   *   MigrateExecutable object.
   *
   * @dataProvider provideFilters
   *
   * @covers ::transform
   */
  public function testTransform($value, $expected_value, $invalid_id = NULL) {
    $configuration = [
      'bypass' => TRUE,
      'map' => [
        'foo' => 'filter_html',
        'baz' => 'php_code',
      ],
    ];
    $plugin = FilterID::create($this->container, $configuration, 'filter_id', []);

    if (isset($invalid_id)) {
      $this->executable
        ->expects($this->exactly(1))
        ->method('saveMessage')
        ->with(
          'Filter ' . $invalid_id . ' could not be mapped to an existing filter plugin; defaulting to filter_null.',
          MigrationInterface::MESSAGE_WARNING
        );
    }

    $row = new Row();
    $output_value = $plugin->transform($value, $this->executable, $row, 'foo');

    $this->assertSame($expected_value, $output_value);
  }

  /**
   * Provides filter ids for testing transformations.
   *
   * @return array
   *   Formatted as $source_id, $tranformed_id, $invalid_id.
   *   When $invalid_id is provided the transformation should fail with the
   *   supplied id.
   */
  public function provideFilters() {
    return [
      'filter ID mapped to plugin that exists' => [
        'foo',
        'filter_html',
      ],
      'filter ID not mapped but unchanged from the source and the plugin exists' => [
        'filter_html',
        'filter_html',
      ],
      'filter ID mapped to plugin that does not exist' => [
        'baz',
        'filter_null',
        'php_code',
      ],
      'filter ID not mapped but unchanged from the source and the plugin does not exist' => [
        'php_code',
        'filter_null',
        'php_code',
      ],
      'filter ID set and the plugin does not exist' => [
        ['filter', 1],
        'filter_null',
        'filter:1',
      ],
    ];
  }

}
