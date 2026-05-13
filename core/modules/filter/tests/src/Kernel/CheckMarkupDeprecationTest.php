<?php

declare(strict_types=1);

namespace Drupal\Tests\filter\Kernel;

use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests deprecation of check_markup().
 */
#[CoversFunction('check_markup')]
#[Group('filter')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class CheckMarkupDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter'];

  /**
   * Test deprecation of check_markup().
   */
  public function testCheckMarkup(): void {
    FilterFormat::create([
      'format' => 'foo',
      'name' => 'Foo',
      'filters' => [
        'filter_html' => [
          'settings' => [
            'allowed_html' => '<p>',
          ],
          'status' => TRUE,
        ],
        'filter_url' => [
          'status' => TRUE,
        ],
      ],
    ])->save();

    $this->expectUserDeprecationMessage("check_markup() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no direct replacement. It's recommended to always return a renderable array without flattening as markup to pass the cacheability metadata. See https://www.drupal.org/node/3588040");
    $formatted = (string) check_markup(
      text: '<p>Visit https://example.com',
      format_id: 'foo',
      filter_types_to_skip: [FilterInterface::TYPE_MARKUP_LANGUAGE],
    );

    // The filter_html filter was applied, and filter_url skipped.
    $this->assertSame('<p>Visit https://example.com</p>', $formatted);
  }

}
