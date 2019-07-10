<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional\d6;

use Drupal\Tests\migrate_drupal_ui\Functional\MultilingualReviewPageTestBase;

/**
 * Tests migrate upgrade review page for Drupal 6.
 *
 * Tests with translation modules and migrate_drupal_multilingual enabled.
 *
 * @group migrate_drupal_6
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
    'config_translation',
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
    // Test missing migrate_drupal.yml.
    'migrate_state_no_upgrade_path',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadFixture(drupal_get_path('module', 'migrate_drupal') . '/tests/fixtures/drupal6.php');
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
      // Aggregator is set not_finished in migrate_sate_not_finished_test.
      'aggregator',
      'blog',
      'blogapi',
      'book',
      'calendarsignup',
      'color',
      'comment',
      'contact',
      'content',
      'content_copy',
      'content_multigroup',
      'content_permissions',
      'date',
      'date_api',
      'date_locale',
      'date_php4',
      'date_popup',
      'date_repeat',
      'date_timezone',
      'date_tools',
      'datepicker',
      'dblog',
      'ddblock',
      'email',
      'event',
      'fieldgroup',
      'filefield',
      'filefield_meta',
      'filter',
      'forum',
      'help',
      'i18nblocks',
      'i18ncontent',
      'i18nmenu',
      'i18npoll',
      'i18nprofile',
      'i18nsync',
      'imageapi',
      'imageapi_gd',
      'imageapi_imagemagick',
      'imagecache',
      'imagecache_ui',
      'imagefield',
      'jquery_ui',
      'link',
      'menu',
      'node',
      'nodeaccess',
      'nodereference',
      'number',
      'openid',
      'optionwidgets',
      'path',
      'phone',
      'php',
      'ping',
      'poll',
      'profile',
      'search',
      'statistics',
      'syslog',
      'system',
      'taxonomy',
      'text',
      'throttle',
      'tracker',
      'translation',
      'trigger',
      'update',
      'upload',
      'user',
      'userreference',
      'variable',
      'variable_admin',
      'views_export',
      'views_ui',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths() {
    return [
      // Block is set not_finished in migrate_sate_not_finished_test.
      'block',
      'devel',
      'devel_generate',
      'devel_node_access',
      'i18n',
      'i18ncck',
      'i18nstrings',
      'i18ntaxonomy',
      'i18nviews',
      'locale',
      'migrate_status_active_test',
      'views',
    ];
  }

}
