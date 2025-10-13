<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Kernel\Plugin\migrate\source\d6;

use Drupal\block\Plugin\migrate\source\d6\BlockTranslation;
use Drupal\Tests\block\Kernel\Plugin\migrate\source\BlockTest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests i18n block source plugin.
 */
#[CoversClass(BlockTranslation::class)]
#[Group('content_translation')]
#[RunTestsInSeparateProcesses]
class BlockTranslationTest extends BlockTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    // Test data is the same as BlockTest, but with the addition of i18n_blocks.
    $tests = parent::providerSource();

    // The source data.
    $tests[0]['source_data']['i18n_blocks'] = [
      [
        'ibid' => 1,
        'module' => 'block',
        'delta' => '1',
        'type' => 0,
        'language' => 'fr',
      ],
      [
        'ibid' => 2,
        'module' => 'block',
        'delta' => '2',
        'type' => 0,
        'language' => 'zu',
      ],
    ];
    $tests[0]['source_data']['variables'] = [
      [
        'name' => 'default_theme',
        'value' => 's:7:"garland";',
      ],
    ];
    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'bid' => 1,
        'module' => 'block',
        'delta' => '1',
        'title' => 'Test Title 01',
        'ibid' => 1,
        'type' => '0',
        'language' => 'fr',
        'default_theme' => 'Garland',
      ],
      [
        'bid' => 2,
        'module' => 'block',
        'delta' => '2',
        'theme' => 'garland',
        'title' => 'Test Title 02',
        'ibid' => 2,
        'type' => '0',
        'language' => 'zu',
        'default_theme' => 'Garland',
      ],
    ];

    return $tests;
  }

}
