<?php

declare(strict_types=1);

namespace Drupal\Tests\filter\Kernel;

use Drupal\filter\Plugin\FilterInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\filter\Entity\FilterFormat;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that security filters are enforced even when marked to be skipped.
 */
#[RunTestsInSeparateProcesses]
#[Group('filter')]
class FilterSecurityKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a dedicated text format for this test.
    FilterFormat::create([
      'format' => 'kernel_filtered_html',
      'name' => 'Kernel Filtered HTML',
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'settings' => [
            'allowed_html' => '<p>',
          ],
        ],
      ],
    ])->save();
  }

  /**
   * Tests that security filters are enforced even when marked to be skipped.
   */
  public function testSkipSecurityFilters(): void {
    $text = "Text with some disallowed tags: <script />, <p><object>unicorn</object></p>, <i><table></i>.";
    $expected = "Text with some disallowed tags: , <p>unicorn</p>, .";

    $this->assertSame(
      $expected,
      (string) check_markup($text, 'kernel_filtered_html', '', []),
      'Expected filter result.'
    );

    $this->assertSame(
      $expected,
      (string) check_markup($text, 'kernel_filtered_html', '', [FilterInterface::TYPE_HTML_RESTRICTOR]),
      'Expected filter result, even when trying to skip security filters.'
    );
  }

}
