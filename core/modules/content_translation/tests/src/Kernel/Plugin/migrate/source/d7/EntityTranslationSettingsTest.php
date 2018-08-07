<?php

namespace Drupal\Tests\content_translation\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests entity translation settings source plugin.
 *
 * @covers \Drupal\content_translation\Plugin\migrate\source\d7\EntityTranslationSettings
 *
 * @group content_translation
 */
class EntityTranslationSettingsTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'content_translation',
    'language',
    'migrate_drupal',
  ];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // Source data when there's no entity type that uses entity translation.
    $tests[0]['source_data']['variable'] = [
      [
        'name' => 'entity_translation_entity_types',
        'value' => 'a:4:{s:7:"comment";i:0;s:4:"node";i:0;s:13:"taxonomy_term";i:0;s:4:"user";i:0;}',
      ],
    ];

    // Source data when there's no bundle settings variables.
    $tests[1]['source_data']['variable'] = [
      [
        'name' => 'entity_translation_entity_types',
        'value' => 'a:4:{s:7:"comment";s:7:"comment";s:4:"node";s:4:"node";s:13:"taxonomy_term";s:13:"taxonomy_term";s:4:"user";s:4:"user";}',
      ],
      [
        'name' => 'entity_translation_taxonomy',
        'value' => 'a:3:{s:6:"forums";b:1;s:4:"tags";b:1;s:4:"test";b:0;}',
      ],
      [
        'name' => 'language_content_type_article',
        'value' => 's:1:"2";',
      ],
      [
        'name' => 'language_content_type_forum',
        'value' => 's:1:"4";',
      ],
      [
        'name' => 'language_content_type_page',
        'value' => 's:1:"4";',
      ],
    ];

    // Source data when there's bundle settings variables.
    $tests[2]['source_data']['variable'] = [
      [
        'name' => 'entity_translation_entity_types',
        'value' => 'a:4:{s:7:"comment";s:7:"comment";s:4:"node";s:4:"node";s:13:"taxonomy_term";s:13:"taxonomy_term";s:4:"user";s:4:"user";}',
      ],
      [
        'name' => 'entity_translation_settings_comment__comment_node_forum',
        'value' => 'a:5:{s:16:"default_language";s:12:"xx-et-author";s:22:"hide_language_selector";i:1;s:21:"exclude_language_none";i:0;s:13:"lock_language";i:0;s:27:"shared_fields_original_only";i:0;}',
      ],
      [
        'name' => 'entity_translation_settings_comment__comment_node_page',
        'value' => 'a:5:{s:16:"default_language";s:12:"xx-et-author";s:22:"hide_language_selector";i:0;s:21:"exclude_language_none";i:0;s:13:"lock_language";i:0;s:27:"shared_fields_original_only";i:1;}',
      ],
      [
        'name' => 'entity_translation_settings_node__forum',
        'value' => 'a:5:{s:16:"default_language";s:12:"xx-et-author";s:22:"hide_language_selector";i:0;s:21:"exclude_language_none";i:0;s:13:"lock_language";i:0;s:27:"shared_fields_original_only";i:0;}',
      ],
      [
        'name' => 'entity_translation_settings_node__page',
        'value' => 'a:5:{s:16:"default_language";s:13:"xx-et-default";s:22:"hide_language_selector";i:1;s:21:"exclude_language_none";i:0;s:13:"lock_language";i:0;s:27:"shared_fields_original_only";i:1;}',
      ],
      [
        'name' => 'entity_translation_settings_taxonomy_term__forums',
        'value' => 'a:5:{s:16:"default_language";s:13:"xx-et-current";s:22:"hide_language_selector";i:0;s:21:"exclude_language_none";i:0;s:13:"lock_language";i:0;s:27:"shared_fields_original_only";i:1;}',
      ],
      [
        'name' => 'entity_translation_settings_taxonomy_term__tags',
        'value' => 'a:5:{s:16:"default_language";s:13:"xx-et-current";s:22:"hide_language_selector";i:1;s:21:"exclude_language_none";i:0;s:13:"lock_language";i:0;s:27:"shared_fields_original_only";i:0;}',
      ],
      [
        'name' => 'entity_translation_settings_user__user',
        'value' => 'a:5:{s:16:"default_language";s:12:"xx-et-author";s:22:"hide_language_selector";i:1;s:21:"exclude_language_none";i:0;s:13:"lock_language";i:0;s:27:"shared_fields_original_only";i:1;}',
      ],
      [
        'name' => 'entity_translation_taxonomy',
        'value' => 'a:3:{s:6:"forums";b:1;s:4:"tags";b:1;s:4:"test";b:0;}',
      ],
      [
        'name' => 'language_content_type_article',
        'value' => 's:1:"2";',
      ],
      [
        'name' => 'language_content_type_forum',
        'value' => 's:1:"4";',
      ],
      [
        'name' => 'language_content_type_page',
        'value' => 's:1:"4";',
      ],
    ];

    // Source data when taxonomy terms are translatable but the
    // 'entity_translation_taxonomy' variable is not set.
    $tests[3]['source_data']['variable'] = [
      [
        'name' => 'entity_translation_entity_types',
        'value' => 'a:4:{s:7:"comment";i:0;s:4:"node";i:0;s:13:"taxonomy_term";i:1;s:4:"user";i:0;}',
      ],
    ];

    // Expected data when there's no entity type that uses entity translation.
    $tests[0]['expected_data'] = [];

    // Expected data when there's no bundle settings variables.
    $tests[1]['expected_data'] = [
      [
        'id' => 'node.forum',
        'target_entity_type_id' => 'node',
        'target_bundle' => 'forum',
        'default_langcode' => 'und',
        'language_alterable' => TRUE,
        'untranslatable_fields_hide' => FALSE,
      ],
      [
        'id' => 'node.page',
        'target_entity_type_id' => 'node',
        'target_bundle' => 'page',
        'default_langcode' => 'und',
        'language_alterable' => TRUE,
        'untranslatable_fields_hide' => FALSE,
      ],
      [
        'id' => 'comment.comment_forum',
        'target_entity_type_id' => 'comment',
        'target_bundle' => 'comment_forum',
        'default_langcode' => 'xx-et-current',
        'language_alterable' => FALSE,
        'untranslatable_fields_hide' => FALSE,
      ],
      [
        'id' => 'comment.comment_node_page',
        'target_entity_type_id' => 'comment',
        'target_bundle' => 'comment_node_page',
        'default_langcode' => 'xx-et-current',
        'language_alterable' => FALSE,
        'untranslatable_fields_hide' => FALSE,
      ],
      [
        'id' => 'taxonomy_term.forums',
        'target_entity_type_id' => 'taxonomy_term',
        'target_bundle' => 'forums',
        'default_langcode' => 'xx-et-default',
        'language_alterable' => FALSE,
        'untranslatable_fields_hide' => FALSE,
      ],
      [
        'id' => 'taxonomy_term.tags',
        'target_entity_type_id' => 'taxonomy_term',
        'target_bundle' => 'tags',
        'default_langcode' => 'xx-et-default',
        'language_alterable' => FALSE,
        'untranslatable_fields_hide' => FALSE,
      ],
      [
        'id' => 'user.user',
        'target_entity_type_id' => 'user',
        'target_bundle' => 'user',
        'default_langcode' => 'xx-et-default',
        'language_alterable' => FALSE,
        'untranslatable_fields_hide' => FALSE,
      ],
    ];

    // Expected data when there's bundle settings variables.
    $tests[2]['expected_data'] = [
      [
        'id' => 'node.forum',
        'target_entity_type_id' => 'node',
        'target_bundle' => 'forum',
        'default_langcode' => 'xx-et-author',
        'language_alterable' => TRUE,
        'untranslatable_fields_hide' => FALSE,
      ],
      [
        'id' => 'node.page',
        'target_entity_type_id' => 'node',
        'target_bundle' => 'page',
        'default_langcode' => 'xx-et-default',
        'language_alterable' => FALSE,
        'untranslatable_fields_hide' => TRUE,
      ],
      [
        'id' => 'comment.comment_forum',
        'target_entity_type_id' => 'comment',
        'target_bundle' => 'comment_forum',
        'default_langcode' => 'xx-et-author',
        'language_alterable' => FALSE,
        'untranslatable_fields_hide' => FALSE,
      ],
      [
        'id' => 'comment.comment_node_page',
        'target_entity_type_id' => 'comment',
        'target_bundle' => 'comment_node_page',
        'default_langcode' => 'xx-et-author',
        'language_alterable' => TRUE,
        'untranslatable_fields_hide' => TRUE,
      ],
      [
        'id' => 'taxonomy_term.forums',
        'target_entity_type_id' => 'taxonomy_term',
        'target_bundle' => 'forums',
        'default_langcode' => 'xx-et-current',
        'language_alterable' => TRUE,
        'untranslatable_fields_hide' => TRUE,
      ],
      [
        'id' => 'taxonomy_term.tags',
        'target_entity_type_id' => 'taxonomy_term',
        'target_bundle' => 'tags',
        'default_langcode' => 'xx-et-current',
        'language_alterable' => FALSE,
        'untranslatable_fields_hide' => FALSE,
      ],
      [
        'id' => 'user.user',
        'target_entity_type_id' => 'user',
        'target_bundle' => 'user',
        'default_langcode' => 'xx-et-author',
        'language_alterable' => FALSE,
        'untranslatable_fields_hide' => TRUE,
      ],
    ];

    // Expected data when taxonomy terms are translatable but the
    // 'entity_translation_taxonomy' variable is not set.
    $tests[3]['expected_data'] = [];

    return $tests;
  }

}
