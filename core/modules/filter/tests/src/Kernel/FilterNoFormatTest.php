<?php

declare(strict_types=1);

namespace Drupal\Tests\filter\Kernel;

use Drupal\filter\FilterFormatRepositoryInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\filter\Traits\ProcessedTextTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests processed text when no format has been passed.
 */
#[Group('filter')]
#[RunTestsInSeparateProcesses]
class FilterNoFormatTest extends KernelTestBase {

  use ProcessedTextTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter'];

  /**
   * Tests text without format.
   */
  public function testProcessedTextNoFormat(): void {
    $this->installConfig(['filter']);

    // Create some text. Include some HTML and line breaks, so we get a good
    // test of the filtering that is applied to it.
    $text = "<strong>" . $this->randomMachineName(32) . "</strong>\n\n<div>" . $this->randomMachineName(32) . "</div>";

    // Make sure that when this text is processed with no text format, it is
    // filtered as though it is in the fallback format.
    $this->assertEquals($this->processText($text), $this->processText($text, \Drupal::service(FilterFormatRepositoryInterface::class)->getFallbackFormatId()));
  }

}
