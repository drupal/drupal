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
  public static $modules = [
    'language',
    'telephone',
    'aggregator',
    'book',
    'forum',
    'statistics',
    'syslog',
    'tracker',
    'update',
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
      'aggregator',
      'block',
      'book',
      'comment',
      'contact',
      'content',
      'date',
      'dblog',
      'email',
      'filefield',
      'filter',
      'forum',
      'imagecache',
      'imagefield',
      'language',
      'link',
      'locale',
      'menu',
      'node',
      'nodereference',
      'optionwidgets',
      'path',
      'profile',
      'search',
      'statistics',
      'syslog',
      'system',
      'taxonomy',
      'text',
      'update',
      'upload',
      'user',
      'userreference',
      // Include modules that do not have an upgrade path, defined in the
      // $noUpgradePath property in MigrateUpgradeForm.
      'blog',
      'blogapi',
      'calendarsignup',
      'color',
      'content_copy',
      'content_multigroup',
      'content_permissions',
      'date_api',
      'date_locale',
      'date_php4',
      'date_popup',
      'date_repeat',
      'date_timezone',
      'date_tools',
      'datepicker',
      'ddblock',
      'event',
      'fieldgroup',
      'filefield_meta',
      'help',
      'i18nstrings',
      'i18nsync',
      'imageapi',
      'imageapi_gd',
      'imageapi_imagemagick',
      'imagecache_ui',
      'jquery_ui',
      'nodeaccess',
      'number',
      'openid',
      'php',
      'ping',
      'poll',
      'throttle',
      'tracker',
      'translation',
      'trigger',
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
      'i18ntaxonomy',
      'i18nviews',
      'phone',
      'views',
    ];
  }

}
