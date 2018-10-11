<?php

namespace Drupal\Tests\language\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests i18ntaxonomy vocabulary setting source plugin.
 *
 * @covers \Drupal\language\Plugin\migrate\source\d6\LanguageContentSettingsTaxonomyVocabulary
 *
 * @group language
 */
class LanguageContentTaxonomyVocabularySettingsTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['taxonomy', 'language', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['vocabulary'] = [
      [
        'vid' => 1,
        'name' => 'Tags',
        'description' => 'Tags description.',
        'help' => 1,
        'relations' => 0,
        'hierarchy' => 0,
        'multiple' => 0,
        'required' => 0,
        'tags' => 1,
        'module' => 'taxonomy',
        'weight' => 0,
        'language' => '',
      ],
      [
        'vid' => 2,
        'name' => 'Categories',
        'description' => 'Categories description.',
        'help' => 1,
        'relations' => 1,
        'hierarchy' => 1,
        'multiple' => 0,
        'required' => 1,
        'tags' => 0,
        'module' => 'taxonomy',
        'weight' => 0,
        'language' => 'zu',
      ],
    ];
    $tests[0]['source_data']['variable'] = [
      [
        'name' => 'i18ntaxonomy_vocabulary',
        'value' => 'a:4:{i:1;s:1:"3";i:2;s:1:"2";i:3;s:1:"3";i:5;s:1:"1";}',
      ],
    ];

    $tests[0]['expected_data'] = [
      [
        'vid' => 1,
        'language' => '',
        'state' => 3,
      ],
      [
        'vid' => 2,
        'language' => 'zu',
        'state' => 2,
      ],
    ];

    return $tests;
  }

}
