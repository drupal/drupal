<?php

namespace Drupal\Tests\block_content\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests i18n content block translations source plugin.
 *
 * @covers \Drupal\block_content\Plugin\migrate\source\d6\BoxTranslation
 *
 * @group content_translation
 */
class BoxTranslationTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block_content', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['database']['boxes'] = [
      [
        'bid' => 1,
        'body' => 'box 1 body',
        'info' => 'box 1 title',
        'format' => '2',
      ],
      [
        'bid' => 2,
        'body' => 'box 2 body',
        'info' => 'box 2 title',
        'format' => '2',
      ],
    ];

    $tests[0]['database']['i18n_strings'] = [
      [
        'lid' => 1,
        'objectid' => 1,
        'type' => 'block',
        'property' => 'title',
        'objectindex' => 1,
        'format' => 0,
      ],
      [
        'lid' => 2,
        'objectid' => 1,
        'type' => 'block',
        'property' => 'body',
        'objectindex' => 1,
        'format' => 0,
      ],
      [
        'lid' => 3,
        'objectid' => 2,
        'type' => 'block',
        'property' => 'body',
        'objectindex' => 2,
        'format' => 2,
      ],
    ];

    $tests[0]['database']['locales_target'] = [
      [
        'lid' => 1,
        'language' => 'fr',
        'translation' => 'fr - title translation',
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
      [
        'lid' => 2,
        'language' => 'fr',
        'translation' => 'fr - body translation',
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
      [
        'lid' => 3,
        'language' => 'zu',
        'translation' => 'zu - body translation',
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
    ];

    $tests[0]['expected_results'] = [
      [
        'lid' => '1',
        'property' => 'title',
        'language' => 'fr',
        'translation' => 'fr - title translation',
        'bid' => '1',
        'format' => '2',
        'title_translated' => 'fr - title translation',
        'body_translated' => 'fr - body translation',
        'title' => 'box 1 title',
        'body' => 'box 1 body',
      ],
      [
        'lid' => '2',
        'property' => 'body',
        'language' => 'fr',
        'translation' => 'fr - body translation',
        'bid' => '1',
        'format' => '2',
        'title_translated' => 'fr - title translation',
        'body_translated' => 'fr - body translation',
        'title' => 'box 1 title',
        'body' => 'box 1 body',
      ],
      [
        'lid' => '3',
        'property' => 'body',
        'language' => 'zu',
        'translation' => 'zu - body translation',
        'bid' => '2',
        'format' => '2',
        'title_translated' => NULL,
        'body_translated' => 'zu - body translation',
        'title' => 'box 2 title',
        'body' => 'box 2 body',
      ],
    ];

    return $tests;
  }

}
