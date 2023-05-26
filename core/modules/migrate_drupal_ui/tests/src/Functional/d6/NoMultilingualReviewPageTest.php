<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional\d6;

use Drupal\Tests\migrate_drupal_ui\Functional\NoMultilingualReviewPageTestBase;

// cspell:ignore multigroup nodeaccess

/**
 * Tests migrate upgrade review page for Drupal 6 without translations.
 *
 * Tests with the translation modules disabled.
 *
 * @group migrate_drupal_6
 * @group migrate_drupal_ui
 */
class NoMultilingualReviewPageTest extends NoMultilingualReviewPageTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'datetime_range',
    'language',
    'telephone',
    'book',
    'forum',
    'statistics',
    'syslog',
    // @todo Remove tracker in https://www.drupal.org/project/drupal/issues/3261452
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
    $this->loadFixture($this->getModulePath('migrate_drupal') . '/tests/fixtures/drupal6.php');
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
      'Blog',
      'Blog API',
      'Book',
      'Calendar Signup',
      // @todo Remove Color in https://www.drupal.org/project/drupal/issues/3270899
      'Color',
      'Comment',
      'Contact',
      'Content',
      'Content Copy',
      'Content Multigroup',
      'Content Permissions',
      'Content translation',
      'Database logging',
      'Date',
      'Date API',
      'Date Locale',
      'Date PHP4',
      'Date Picker',
      'Date Popup',
      'Date Repeat API',
      'Date Timezone',
      'Date Tools',
      'Dynamic display block',
      'Email',
      'Event',
      'Fieldgroup',
      'FileField',
      'FileField Meta',
      'Filter',
      'Forum',
      'Help',
      'ImageAPI',
      'ImageAPI GD2',
      'ImageAPI ImageMagick',
      'ImageCache',
      'ImageCache UI',
      'ImageField',
      'Link',
      'Locale',
      'Menu',
      'Node',
      'Nodeaccess',
      'Node Reference',
      'Node Reference URL Widget',
      'Number',
      'OpenID',
      'PHP filter',
      'Path',
      'Phone - CCK',
      'Ping',
      'Poll',
      'Profile',
      'Search',
      'Statistics',
      'Syslog',
      'System',
      'Taxonomy',
      'Text',
      'Throttle',
      // @todo Remove tracker in https://www.drupal.org/project/drupal/issues/3261452
      'Tracker',
      'Trigger',
      'Update status',
      'Upload',
      'User',
      'User Reference',
      'Variable API',
      'Variable admin',
      'Views UI',
      'Views exporter',
      'jQuery UI',
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
      'Aggregator',
      // Block is set not_finished in migrate_state_not_finished_test.
      'Block',
      'Block translation',
      'CCK translation',
      'Content type translation',
      'Devel',
      'Devel generate',
      'Devel node access',
      'Internationalization',
      'Menu translation',
      'migrate_status_active_test',
      // Option Widgets is set not_finished in migrate_state_not_finished_test.
      'Option Widgets',
      'Poll aggregate',
      'Profile translation',
      'String translation',
      'Synchronize translations',
      'Taxonomy translation',
      'Views',
      'Views translation',
    ];
  }

}
