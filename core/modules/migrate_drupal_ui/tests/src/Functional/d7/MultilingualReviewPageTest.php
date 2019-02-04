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
      'aggregator',
      'block',
      'book',
      'color',
      'comment',
      'contact',
      'date',
      'dblog',
      'email',
      'field',
      'field_sql_storage',
      'file',
      'filter',
      'forum',
      'image',
      'i18n_block',
      'language',
      'link',
      'list',
      'locale',
      'menu',
      'node',
      'number',
      'options',
      'path',
      'phone',
      'rdf',
      'search',
      'shortcut',
      'statistics',
      'syslog',
      'system',
      'taxonomy',
      'text',
      'tracker',
      'update',
      'user',
      // Include modules that do not have an upgrade path, defined in the
      // $noUpgradePath property in MigrateUpgradeForm.
      'blog',
      'bulk_export',
      'contextual',
      'ctools',
      'ctools_access_ruleset',
      'ctools_ajax_sample',
      'ctools_custom_content',
      'dashboard',
      'date_all_day',
      'date_api',
      'date_context',
      'date_migrate',
      'date_popup',
      'date_repeat',
      'date_repeat_field',
      'date_tools',
      'date_views',
      'entity',
      'entity_feature',
      'entity_token',
      'entityreference',
      'entity_translation',
      'field_ui',
      'help',
      'openid',
      'overlay',
      'page_manager',
      'php',
      'poll',
      'search_embedded_form',
      'search_extra_type',
      'search_node_tags',
      'simpletest',
      'stylizer',
      'term_depth',
      'title',
      'toolbar',
      'translation',
      'trigger',
      'views_content',
      'views_ui',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths() {
    return [
      'entity_translation_i18n_menu',
      'entity_translation_upgrade',
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
      'profile',
      'variable',
      'variable_admin',
      'variable_realm',
      'variable_store',
      'variable_views',
      'views',
    ];
  }

}
