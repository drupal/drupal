<?php

namespace Drupal\Tests\aggregator\Functional\migrate_drupal_ui\d6;

use Drupal\Tests\migrate_drupal_ui\Functional\MultilingualReviewPageTestBase;

// cspell:ignore multigroup nodeaccess

/**
 * Tests migrate upgrade review page for Drupal 6 for the aggregator module.
 *
 * Tests with translation modules enabled.
 *
 * @group aggregator
 * @group legacy
 */
class MultilingualReviewPageTest extends MultilingualReviewPageTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'aggregator',
    'content_translation',
    'config_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loadFixture($this->getModulePath('aggregator') . '/tests/fixtures/drupal6.php');
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
      'Aggregator',
      'Block',
      'Block translation',
      'Blog',
      'Blog API',
      'CCK translation',
      'Calendar Signup',
      'Color',
      'Comment',
      'Contact',
      'Content',
      'Content Copy',
      'Content Multigroup',
      'Content Permissions',
      'Content translation',
      'Content type translation',
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
      'Help',
      'ImageAPI',
      'ImageAPI GD2',
      'ImageAPI ImageMagick',
      'ImageCache',
      'ImageCache UI',
      'ImageField',
      'Internationalization',
      'Link',
      'Locale',
      'Menu',
      'Menu',
      'Menu translation',
      'Node Reference',
      'Nodeaccess',
      'Number',
      'OpenID',
      'Option Widgets',
      'PHP filter',
      'Path',
      'Phone - CCK',
      'Ping',
      'Poll',
      'Poll aggregate',
      'Profile',
      'Profile translation',
      'Search',
      'String translation',
      'Synchronize translations',
      'System',
      'Taxonomy',
      'Taxonomy translation',
      'Text',
      'Throttle',
      'Tracker',
      'Trigger',
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
  protected function getMissingPaths() {
    return [
      'Book',
      'Devel',
      'Devel generate',
      'Devel node access',
      'Forum',
      'Statistics',
      'Syslog',
      'Update status',
      'Views',
      'Views translation',
      'migrate_status_active_test',
    ];
  }

}
