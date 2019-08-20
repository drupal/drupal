<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional\d7;

use Drupal\Tests\migrate_drupal_ui\Functional\MultilingualReviewPageTestBase;

/**
 * Tests migrate upgrade review page for Drupal 7.
 *
 * Tests with translation modules and migrate_drupal_multilingual enabled.
 *
 * @group migrate_drupal_7
 * @group migrate_drupal_ui
 *
 * @group legacy
 */
class MultilingualReviewPageTest extends MultilingualReviewPageTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'language',
    'content_translation',
    'telephone',
    'aggregator',
    'book',
    'forum',
    'statistics',
    'syslog',
    'tracker',
    'update',
    // Required for translation migrations.
    'migrate_drupal_multilingual',
    // Test migrations states.
    'migrate_state_finished_test',
    'migrate_state_not_finished_test',
    // Test missing migrate_drupal.yml.
    'migrate_state_no_file_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadFixture(drupal_get_path('module', 'migrate_drupal') . '/tests/fixtures/drupal7.php');
  }

  /**
   * {@inheritdoc}
   */
  protected function getSourceBasePath() {
    return __DIR__ . '/files';
  }

  /**
   * {@inheritdoc}
   */
  protected function getAvailablePaths() {
    return [
      'blog',
      'book',
      'bulk_export',
      'color',
      'comment',
      'contact',
      'contextual',
      'ctools',
      'ctools_access_ruleset',
      'ctools_ajax_sample',
      'ctools_custom_content',
      'dashboard',
      'date',
      'date_api',
      'date_all_day',
      'date_context',
      'date_migrate',
      'date_popup',
      'date_repeat',
      'date_repeat_field',
      'date_tools',
      'date_views',
      'dblog',
      'email',
      'entity',
      'entity_feature',
      'entity_token',
      'entity_translation',
      'entityreference',
      'field',
      'field_sql_storage',
      'field_ui',
      'file',
      'filter',
      'forum',
      'help',
      'i18n_block',
      'image',
      'link',
      'list',
      'locale',
      'menu',
      'number',
      'openid',
      'options',
      'overlay',
      'page_manager',
      'path',
      'phone',
      'php',
      'poll',
      'profile',
      'rdf',
      'search',
      'search_embedded_form',
      'search_extra_type',
      'search_node_tags',
      'shortcut',
      'simpletest',
      'statistics',
      'stylizer',
      'syslog',
      'system',
      'taxonomy',
      'term_depth',
      'text',
      'title',
      'toolbar',
      'tracker',
      'translation',
      'trigger',
      'update',
      'user',
      'views_content',
      'views_ui',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths() {
    return [
      // Action is set not_finished in migrate_sate_not_finished_test.
      // Aggregator is set not_finished in migrate_sate_not_finished_test.
      'aggregator',
      // Block is set not_finished in migrate_sate_not_finished_test.
      'block',
      'breakpoints',
      'entity_translation_i18n_menu',
      'entity_translation_upgrade',
      // Flexslider_picture is a sub module of Picture module. Only the
      // styles from picture are migrated.
      'flexslider_picture',
      'i18n',
      'i18n_contact',
      'i18n_field',
      'i18n_forum',
      'i18n_menu',
      'i18n_node',
      'i18n_path',
      'i18n_redirect',
      'i18n_select',
      'i18n_string',
      'i18n_sync',
      'i18n_taxonomy',
      'i18n_translation',
      'i18n_user',
      'i18n_variable',
      'node',
      'picture',
      'migrate_status_active_test',
      'variable',
      'variable_admin',
      'variable_realm',
      'variable_store',
      'variable_views',
      'views',
    ];
  }

}
