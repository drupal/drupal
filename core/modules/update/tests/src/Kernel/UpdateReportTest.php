<?php

namespace Drupal\Tests\update\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests update report functionality.
 *
 * @covers template_preprocess_update_report()
 * @group update
 */
class UpdateReportTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'update',
  ];

  /**
   * @dataProvider providerTemplatePreprocessUpdateReport
   */
  public function testTemplatePreprocessUpdateReport($variables) {
    \Drupal::moduleHandler()->loadInclude('update', 'inc', 'update.report');

    // The function should run without an exception being thrown when the value
    // of $variables['data'] is not set or is not an array.
    template_preprocess_update_report($variables);

    // Test that the key "no_updates_message" has been set.
    $this->assertArrayHasKey('no_updates_message', $variables);
  }

  /**
   * Provides data for testTemplatePreprocessUpdateReport().
   *
   * @return array
   *   Array of $variables for template_preprocess_update_report().
   */
  public function providerTemplatePreprocessUpdateReport() {
    return [
      '$variables with data not set' => [
        [],
      ],
      '$variables with data as an interger' => [
        ['data' => 4],
      ],
      '$variables with data as a string' => [
        ['data' => 'I am a string'],
      ],
    ];
  }

}
