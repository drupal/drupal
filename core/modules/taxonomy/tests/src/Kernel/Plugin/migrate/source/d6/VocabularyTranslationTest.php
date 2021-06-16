<?php

namespace Drupal\Tests\taxonomy\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D6 i18n vocabulary source plugin.
 *
 * @covers \Drupal\taxonomy\Plugin\migrate\source\d6\VocabularyTranslation
 * @group taxonomy
 */
class VocabularyTranslationTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0][0]['i18n_strings'] = [
      [
        'lid' => 1,
        'objectid' => 1,
        'type' => 'vocabulary',
        'property' => 'name',
        'objectindex' => 1,
        'format' => 0,
      ],
      [
        'lid' => 2,
        'objectid' => 2,
        'type' => 'vocabulary',
        'property' => 'name',
        'objectindex' => 2,
        'format' => 0,
      ],
      [
        'lid' => 3,
        'objectid' => 3,
        'type' => 'vocabulary',
        'property' => 'name',
        'objectindex' => 3,
        'format' => 0,
      ],
    ];

    $tests[0][0]['locales_target'] = [
      [
        'lid' => 1,
        'language' => 'fr',
        'translation' => 'fr - vocabulary 1',
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
      [
        'lid' => 2,
        'language' => 'fr',
        'translation' => 'fr - vocabulary 2',
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
    ];

    $tests[0][0]['vocabulary'] = [
      [
        'vid' => 1,
        'name' => 'vocabulary 1',
        'description' => 'description of vocabulary 1',
        'help' => 1,
        'relations' => 1,
        'hierarchy' => 1,
        'multiple' => 0,
        'required' => 0,
        'tags' => 0,
        'module' => 'taxonomy',
        'weight' => 4,
        'language' => '',
      ],
      [
        'vid' => 2,
        'name' => 'vocabulary 2',
        'description' => 'description of vocabulary 2',
        'help' => 1,
        'relations' => 1,
        'hierarchy' => 1,
        'multiple' => 0,
        'required' => 0,
        'tags' => 0,
        'module' => 'taxonomy',
        'weight' => 5,
        'language' => '',
      ],
      [
        'vid' => 3,
        'name' => 'vocabulary 3',
        'description' => 'description of vocabulary 3',
        'help' => 1,
        'relations' => 1,
        'hierarchy' => 1,
        'multiple' => 0,
        'required' => 0,
        'tags' => 0,
        'module' => 'taxonomy',
        'weight' => 5,
        'language' => '',
      ],
    ];

    $tests[0]['expected_results'] = [
      [
        'vid' => 1,
        'name' => 'vocabulary 1',
        'description' => 'description of vocabulary 1',
        'lid' => '1',
        'type' => 'vocabulary',
        'property' => 'name',
        'objectid' => '1',
        'lt_lid' => '1',
        'translation' => 'fr - vocabulary 1',
        'language' => 'fr',
      ],
      [
        'vid' => 2,
        'name' => 'vocabulary 2',
        'description' => 'description of vocabulary 2',
        'lid' => '2',
        'type' => 'vocabulary',
        'property' => 'name',
        'objectid' => '2',
        'lt_lid' => '2',
        'translation' => 'fr - vocabulary 2',
        'language' => 'fr',
      ],
    ];
    return $tests;
  }

}
