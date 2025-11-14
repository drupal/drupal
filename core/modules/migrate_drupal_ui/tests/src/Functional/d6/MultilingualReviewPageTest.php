<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_drupal_ui\Functional\d6;

use Drupal\Tests\migrate_drupal_ui\Functional\MultilingualReviewPageTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cspell:ignore multigroup nodeaccess
/**
 * Tests migrate upgrade review page for Drupal 6.
 *
 * Tests with translation modules enabled.
 */
#[Group('migrate_drupal_6')]
#[Group('migrate_drupal_ui')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class MultilingualReviewPageTest extends MultilingualReviewPageTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'datetime_range',
    'language',
    'content_translation',
    'config_translation',
    'telephone',
    'syslog',
    'update',
    // Test migrations states.
    'migrate_state_finished_test',
    'migrate_state_not_finished_test',
    // Test missing migrate_drupal.yml.
    'migrate_state_no_upgrade_path',
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
  protected function getSourceBasePath(): string {
    return __DIR__ . '/files';
  }

  /**
   * {@inheritdoc}
   */
  protected function getAvailablePaths(): array {
    return [
      'Block translation',
      'Blog',
      'Blog API',
      'CCK translation',
      'Calendar Signup',
      'Comment',
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
      'Menu translation',
      'Node',
      'Node Reference',
      'Node Reference URL Widget',
      'Nodeaccess',
      'Number',
      'OpenID',
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
      'Syslog',
      'System',
      'Taxonomy translation',
      'Taxonomy',
      'Text',
      'Throttle',
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
  protected function getMissingPaths(): array {
    return [
      'Aggregator',
      'Book',
      // Block is set not_finished in migrate_state_not_finished_test.
      'Block',
      'Color',
      'Contact',
      'Devel',
      'Devel generate',
      'Devel node access',
      'Forum',
      'Statistics',
      'Tracker',
      // Option Widgets is set not_finished in migrate_state_not_finished_test.
      'Option Widgets',
      'Views',
      'Views translation',
      'migrate_status_active_test',
    ];
  }

}
