<?php

namespace Drupal\Tests\taxonomy\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D6 i18n term localized source plugin.
 *
 * @covers \Drupal\taxonomy\Plugin\migrate\source\d6\TermLocalizedTranslation
 * @group taxonomy
 */
class TermLocalizedTranslationTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['taxonomy', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['term_data'] = [
      [
        'tid' => 1,
        'vid' => 5,
        'name' => 'name value 1',
        'description' => 'description value 1',
        'weight' => 0,
        'language' => NULL,
      ],
      [
        'tid' => 2,
        'vid' => 6,
        'name' => 'name value 2',
        'description' => 'description value 2',
        'weight' => 0,
        'language' => NULL,
      ],
      [
        'tid' => 3,
        'vid' => 6,
        'name' => 'name value 3',
        'description' => 'description value 3',
        'weight' => 0,
        'language' => NULL,
      ],
      [
        'tid' => 4,
        'vid' => 5,
        'name' => 'name value 4',
        'description' => 'description value 4',
        'weight' => 1,
        'language' => NULL,
      ],
    ];
    $tests[0]['source_data']['term_hierarchy'] = [
      [
        'tid' => 1,
        'parent' => 0,
      ],
      [
        'tid' => 2,
        'parent' => 0,
      ],
      [
        'tid' => 3,
        'parent' => 0,
      ],
      [
        'tid' => 4,
        'parent' => 1,
      ],
    ];
    $tests[0]['source_data']['i18n_strings'] = [
      [
        'lid' => 6,
        'objectid' => 1,
        'type' => 'term',
        'property' => 'name',
        'objectindex' => '1',
        'format' => 0,
      ],
      [
        'lid' => 7,
        'objectid' => 1,
        'type' => 'term',
        'property' => 'description',
        'objectindex' => '1',
        'format' => 0,
      ],
      [
        'lid' => 8,
        'objectid' => 3,
        'type' => 'term',
        'property' => 'name',
        'objectindex' => '3',
        'format' => 0,
      ],
    ];
    $tests[0]['source_data']['locales_target'] = [
      [
        'lid' => 6,
        'language' => 'fr',
        'translation' => 'fr - name value 1 translation',
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
      [
        'lid' => 7,
        'language' => 'fr',
        'translation' => 'fr - description value 1 translation',
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
      [
        'lid' => 8,
        'language' => 'zu',
        'translation' => 'zu - description value 2 translation',
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'tid' => 1,
        'vid' => 5,
        'name' => 'name value 1',
        'description' => 'description value 1',
        'weight' => 0,
        'parent' => [0],
        'property' => 'name',
        'language' => 'fr',
        'name_translated' => 'fr - name value 1 translation',
        'description_translated' => 'fr - description value 1 translation',
      ],
      [
        'tid' => 1,
        'vid' => 5,
        'name' => 'name value 1',
        'description' => 'description value 1',
        'weight' => 0,
        'parent' => [0],
        'property' => 'description',
        'language' => 'fr',
        'name_translated' => 'fr - name value 1 translation',
        'description_translated' => 'fr - description value 1 translation',
      ],
      [
        'tid' => 3,
        'vid' => 6,
        'name' => 'name value 3',
        'description' => 'description value 3',
        'weight' => 0,
        'parent' => [0],
        'property' => 'name',
        'language' => 'zu',
        'name_translated' => 'zu - description value 2 translation',
        'description_translated' => NULL,
      ],
    ];

    $tests[0]['expected_count'] = NULL;
    // Empty configuration will return terms for all vocabularies.
    $tests[0]['configuration'] = [];

    return $tests;
  }

}
