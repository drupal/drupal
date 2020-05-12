<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional\d6;

use Drupal\Tests\migrate_drupal_ui\Functional\NoMultilingualReviewPageTestBase;

/**
 * Tests migrate upgrade review page for Drupal 6 without translations.
 *
 * Tests with the translation modules and migrate_drupal_multilingual module
 * disabled.
 *
 * @group migrate_drupal_6
 * @group migrate_drupal_ui
 */
class NoMultilingualReviewPageTest extends NoMultilingualReviewPageTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'telephone',
    'aggregator',
    'book',
    'forum',
    'statistics',
    'syslog',
    'tracker',
    'update',
    // Test migrations states.
    'migrate_state_finished_test',
    'migrate_state_not_finished_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
      'imageapi',
      'imageapi_gd',
      'imageapi_imagemagick',
      'imagecache',
      'imagecache_ui',
      'imagefield',
      'jquery_ui',
      'link',
      'locale',
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
  protected function getIncompletePaths() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths() {
    return [
      // Block is set not_finished in migrate_state_not_finished_test.
      'block',
      'devel',
      'devel_generate',
      'devel_node_access',
      'i18n',
      'i18nblocks',
      'i18ncck',
      'i18ncontent',
      'i18nmenu',
      'i18npoll',
      'i18nprofile',
      'i18nstrings',
      'i18nsync',
      'i18ntaxonomy',
      'i18nviews',
      'migrate_status_active_test',
      'views',
    ];
  }

}
