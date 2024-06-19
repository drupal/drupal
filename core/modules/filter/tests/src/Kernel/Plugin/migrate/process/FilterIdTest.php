<?php

declare(strict_types=1);

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
  protected static $modules = ['filter'];

  /**
   * The mocked MigrateExecutable.
   *
   * @var \Drupal\migrate\MigrateExecutableInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $executable;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
   * @param bool $stop_pipeline
   *   (optional) Set to TRUE if we expect the filter to be skipped because it
   *   is a transformation-only filter.
   *
   * @dataProvider provideFilters
   *
   * @covers ::transform
   */
  public function testTransform($value, $expected_value, $invalid_id = NULL, $stop_pipeline = FALSE): void {
    $configuration = [
      'bypass' => TRUE,
      'map' => [
        'foo' => 'filter_html',
        'baz' => 'php_code',
      ],
    ];
    $plugin = FilterID::create($this->container, $configuration, 'filter_id', []);

    if ($stop_pipeline) {
      $this->executable
        ->expects($this->exactly(1))
        ->method('saveMessage')
        ->with(
          sprintf('Filter %s could not be mapped to an existing filter plugin; omitted since it is a transformation-only filter. Install and configure a successor after the migration.', $value),
          MigrationInterface::MESSAGE_INFORMATIONAL
        );
    }

    if (isset($invalid_id)) {
      $this->executable
        ->expects($this->exactly(1))
        ->method('saveMessage')
        ->with(
          sprintf('Filter %s could not be mapped to an existing filter plugin; defaulting to %s and dropping all settings. Either redo the migration with the module installed that provides an equivalent filter, or modify the text format after the migration to remove this filter if it is no longer necessary.', $invalid_id, $expected_value),
          MigrationInterface::MESSAGE_WARNING
        );
    }

    $row = new Row();
    $output_value = $plugin->transform($value, $this->executable, $row, 'foo');

    $this->assertSame($expected_value, $output_value);
    $this->assertSame($stop_pipeline, $plugin->isPipelineStopped());
  }

  /**
   * Provides filter ids for testing transformations.
   *
   * @return array
   *   Formatted as $source_id, $transformed_id, $invalid_id.
   *   When $invalid_id is provided the transformation should fail with the
   *   supplied id.
   */
  public static function provideFilters() {
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
      'transformation-only D7 contrib filter' => [
        'editor_align',
        NULL,
        NULL,
        TRUE,
      ],
      'non-transformation-only D7 contrib filter' => [
        'bbcode',
        'filter_null',
      ],
    ];
  }

}
