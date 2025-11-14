<?php

declare(strict_types=1);

namespace Drupal\Tests\contact\Functional\migrate_drupal_ui\d6;

use Drupal\Tests\migrate_drupal_ui\Functional\NoMultilingualReviewPageTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cspell:ignore multigroup
/**
 * Tests migrate upgrade review page for Drupal 6 without translations.
 *
 * Tests with the translation modules disabled.
 */
#[Group('contact')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class NoMultilingualReviewPageTest extends NoMultilingualReviewPageTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'contact',
    'migrate_drupal_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loadFixture($this->getModulePath('contact') . '/tests/fixtures/drupal6.php');
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
      'Block',
      'Blog',
      'Blog API',
      'Calendar Signup',
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
      'Help',
      'ImageAPI',
      'ImageAPI GD2',
      'ImageAPI ImageMagick',
      'ImageCache',
      'ImageCache UI',
      'ImageField',
      'Link',
      'Menu',
      'Node',
      'Node Reference',
      'Node Reference URL Widget',
      'Number',
      'OpenID',
      'Option Widgets',
      'Path',
      'Ping',
      'Poll',
      'Profile',
      'Search',
      'System',
      'Taxonomy',
      'Text',
      'Throttle',
      'Trigger',
      'Upload',
      'User',
      'User Reference',
      'Variable API',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getIncompletePaths(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths(): array {
    return [
      'Aggregator',
      'Block translation',
      'Book',
      'CCK translation',
      'Color',
      'Content type translation',
      'Forum',
      'Internationalization',
      'Locale',
      'Menu translation',
      'Poll aggregate',
      'Profile translation',
      'Statistics',
      'String translation',
      'Synchronize translations',
      'Syslog',
      'Taxonomy translation',
      'Tracker',
      'Update status',
      'Views translation',
      'migrate_status_active_test',
    ];
  }

}
