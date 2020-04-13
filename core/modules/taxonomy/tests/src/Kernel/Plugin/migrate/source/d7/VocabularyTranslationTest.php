<?php

namespace Drupal\Tests\taxonomy\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D7 i18n vocabulary source plugin.
 *
 * @covers \Drupal\taxonomy\Plugin\migrate\source\d7\VocabularyTranslation
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
    $tests[0]['source_data']['i18n_string'] = [
      [
        'lid' => '1',
        'textgroup' => 'taxonomy',
        'context' => 'vocabulary:1:name',
        'objectid' => '1',
        'type' => 'vocabulary',
        'property' => 'name',
        'objectindex' => '1',
        'format' => '',
      ],
      [
        'lid' => '2',
        'textgroup' => 'taxonomy',
        'context' => 'vocabulary:1:description',
        'objectid' => '1',
        'type' => 'vocabulary',
        'property' => 'description',
        'objectindex' => '1',
        'format' => '',
      ],
      [
        'lid' => '764',
        'textgroup' => 'field',
        'context' => 'field_color:blog:label',
        'objectid' => 'blog',
        'type' => 'field_color',
        'property' => 'label',
        'objectindex' => '0',
        'format' => '',
      ],
    ];

    $tests[0]['source_data']['locales_target'] = [
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
        'translation' => 'fr - description of vocabulary 1',
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
      [
        'lid' => '764',
        'translation' => 'Color',
        'language' => 'fr',
        'plid' => '0',
        'plural' => '0',
        'i18n_status' => '0',
      ],
    ];

    $tests[0]['source_data']['taxonomy_vocabulary'] = [
      [
        'vid' => 1,
        'name' => 'vocabulary 1',
        'machine_name' => 'vocabulary_1',
        'description' => 'description of vocabulary 1',
        'hierarchy' => 1,
        'module' => 'taxonomy',
        'weight' => 4,
        'language' => 'und',
        'i18n_mode' => '4',

      ],
      [
        'vid' => 2,
        'name' => 'vocabulary 2',
        'machine_name' => 'vocabulary_1',
        'description' => 'description of vocabulary 2',
        'hierarchy' => 1,
        'module' => 'taxonomy',
        'weight' => 4,
        'language' => 'und',
        'i18n_mode' => '4',
      ],
    ];

    $tests[0]['expected_results'] = [
      [
        'vid' => 1,
        'name' => 'vocabulary 1',
        'machine_name' => 'vocabulary_1',
        'description' => 'description of vocabulary 1',
        'hierarchy' => 1,
        'module' => 'taxonomy',
        'weight' => 4,
        'i18n_mode' => '4',
        'lid' => '1',
        'type' => 'vocabulary',
        'property' => 'name',
        'objectid' => '1',
        'lt_lid' => '1',
        'translation' => 'fr - vocabulary 1',
        'v_language' => 'und',
        'textgroup' => 'taxonomy',
        'context' => 'vocabulary:1:name',
        'objectindex' => '1',
        'format' => '',
        'language' => 'fr',
        'plid' => '0',
        'plural' => '0',
        'i18n_status' => '0',
      ],
      [
        'vid' => 1,
        'name' => 'vocabulary 1',
        'machine_name' => 'vocabulary_1',
        'description' => 'description of vocabulary 1',
        'hierarchy' => 1,
        'module' => 'taxonomy',
        'weight' => 4,
        'i18n_mode' => '4',
        'lid' => '2',
        'type' => 'vocabulary',
        'property' => 'description',
        'objectid' => '1',
        'lt_lid' => '2',
        'translation' => 'fr - description of vocabulary 1',
        'v_language' => 'und',
        'textgroup' => 'taxonomy',
        'context' => 'vocabulary:1:description',
        'objectindex' => '1',
        'format' => '',
        'language' => 'fr',
        'plid' => '0',
        'plural' => '0',
        'i18n_status' => '0',
      ],
    ];

    $tests[1] = $tests[0];

    // Test without the language and i18n_mode columns in taxonomy_vocabulary.
    foreach ($tests[1]['source_data']['taxonomy_vocabulary'] as &$data) {
      unset($data['language']);
      unset($data['i18n_mode']);
    }
    foreach ($tests[1]['expected_results'] as &$data) {
      unset($data['v_language']);
      unset($data['i18n_mode']);
    }
    return $tests;
  }

}
