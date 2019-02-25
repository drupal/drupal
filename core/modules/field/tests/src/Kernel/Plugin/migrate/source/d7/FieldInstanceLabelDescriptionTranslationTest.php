<?php

namespace Drupal\Tests\field\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the field label and description translation source plugin.
 *
 * @covers \Drupal\field\Plugin\migrate\source\d7\FieldLabelDescriptionTranslation
 * @group migrate_drupal
 */
class FieldInstanceLabelDescriptionTranslationTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['config_translation', 'migrate_drupal', 'field'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $test = [];

    // The source data.
    $test[0]['source_data'] = [
      'i18n_string' => [
        [
          'lid' => 10,
          'textgroup' => 'field',
          'objectid' => 'story',
          'type' => 'field_image',
          'property' => 'label',
        ],
        [
          'lid' => 11,
          'textgroup' => 'field',
          'objectid' => 'story',
          'type' => 'field_image',
          'property' => 'description',
        ],
        [
          'lid' => 12,
          'textgroup' => 'field',
          'objectid' => 'forum',
          'type' => 'taxonomy_forums',
          'property' => 'label',
        ],
      ],
      'locales_target' => [
        [
          'lid' => 10,
          'translation' => 'fr - story label',
          'language' => 'fr',
          'plid' => 0,
          'plural' => 0,
          'i18n_status' => 0,
        ],
        [
          'lid' => 11,
          'translation' => 'fr - story description',
          'language' => 'fr',
          'plid' => 0,
          'plural' => 0,
          'i18n_status' => 0,
        ],
        [
          'lid' => 12,
          'translation' => 'zu - term reference',
          'language' => 'zu',
          'plid' => 0,
          'plural' => 0,
          'i18n_status' => 0,
        ],
      ],
      'field_config_instance' => [
        [
          'id' => '2',
          'field_id' => '2',
          'field_name' => 'field_image',
          'entity_type' => 'node',
          'bundle' => 'story',
          'data' => 'a:0:{}',
          'deleted' => '0',
        ],
        [
          'id' => '3',
          'field_id' => '3',
          'field_name' => 'field_image',
          'entity_type' => 'node',
          'bundle' => 'article',
          'data' => 'a:0:{}',
          'deleted' => '0',
        ],
        [
          'id' => '3',
          'field_id' => '4',
          'field_name' => 'field_term_reference',
          'entity_type' => 'taxonomy_term',
          'bundle' => 'trees',
          'data' => 'a:0:{}',
          'deleted' => '0',
        ],
      ],
    ];

    $test[0]['expected_results'] = [
      [
        'property' => 'label',
        'translation' => "fr - story label",
        'language' => 'fr',
        'lid' => '10',
      ],
      [
        'property' => 'description',
        'translation' => 'fr - story description',
        'language' => 'fr',
        'lid' => '11',
      ],
      [
        'property' => 'label',
        'translation' => 'zu - term reference',
        'language' => 'zu',
        'lid' => '12',
      ],
    ];
    return $test;
  }

}
