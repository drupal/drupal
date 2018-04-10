<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional\d7;

use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeReviewPageTestBase;

/**
 * Tests migrate upgrade review page for Drupal 7.
 *
 * @group migrate_drupal_7
 * @group migrate_drupal_ui
 */
class MigrateUpgrade7ReviewPageTest extends MigrateUpgradeReviewPageTestBase {

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
      'profile',
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
      'rdf',
      'views',
    ];
  }

}
