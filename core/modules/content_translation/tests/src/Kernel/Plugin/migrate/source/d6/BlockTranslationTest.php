<?php

namespace Drupal\Tests\content_translation\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\block\Kernel\Plugin\migrate\source\BlockTest;

/**
 * Tests i18n block source plugin.
 *
 * @covers \Drupal\content_translation\Plugin\migrate\source\d6\BlockTranslation
 *
 * @group content_translation
 */
class BlockTranslationTest extends BlockTest {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['content_translation'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
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
      ],
    ];

    return $tests;
  }

}
