<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the output of the file upload help.
 */
#[Group('file')]
#[RunTestsInSeparateProcesses]
class UploadHelpTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file', 'system'];

  /**
   * Verify the size limit text does not appear if the size is unlimited.
   */
  public function testUnlimitedFileSize(): void {
    $help_text = [
      '#theme' => 'file_upload_help',
      '#upload_validators' => [
        'FileSizeLimit' => ['fileLimit' => '0'],
      ],
      '#cardinality' => 1,
    ];
    /** @var \Drupal\Component\Render\MarkupInterface $output */
    $output = \Drupal::service('renderer')->renderInIsolation($help_text);
    $this->assertEquals("One file only.\n", (string) $output);
  }

}
