<?php

declare(strict_types=1);

namespace Drupal\Tests\contact\Functional\migrate_drupal_ui\d7;

use Drupal\Tests\migrate_drupal_ui\Functional\NoMultilingualReviewPageTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal 7 upgrade without translations.
 *
 * The test method is provided by the MigrateUpgradeTestBase class.
 */
#[Group('contact')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class NoMultilingualReviewPageTest extends NoMultilingualReviewPageTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_translation',
    'contact',
    'content_translation',
    'language',
    'migrate_drupal_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loadFixture($this->getModulePath('contact') . '/tests/fixtures/drupal7.php');
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
      'Block languages',
      'Block',
      'Blog',
      'Bulk Export',
      'Chaos tools',
      'Comment',
      'Contact',
      'Content translation',
      'Contextual links',
      'Dashboard',
      'Database logging',
      'Date API',
      'Date',
      'Date All Day',
      'Entity API',
      'Entity Reference',
      'Entity Translation',
      'Field SQL storage',
      'Field UI',
      'Field',
      'Field translation',
      'File',
      'Filter',
      'Help',
      'Image',
      'Internationalization',
      'Link',
      'List',
      'Locale',
      'Menu translation',
      'Menu',
      'Node',
      'Number',
      'OpenID',
      'Options',
      'Overlay',
      'PHP filter',
      'Page manager',
      'Path',
      'Poll',
      'Profile',
      'Search',
      'Shortcut',
      'String translation',
      'Synchronize translations',
      'System',
      'Taxonomy translation',
      'Taxonomy',
      'Text',
      'Toolbar',
      'Trigger',
      'User',
      'Variable translation',
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
      'Book',
      'Color',
      'Contact translation',
      'Entity Translation Menu',
      'Entity Translation Upgrade',
      'Forum',
      'Multilingual content',
      'Multilingual forum',
      'Multilingual select',
      'Path translation',
      'RDF',
      'Statistics',
      'Syslog',
      'Tracker',
      'Translation redirect',
      'Translation sets',
      'Update manager',
      'User mail translation',
      'Variable',
      'Variable admin',
      'Variable realm',
      'Variable store',
      'Variable views',
      'migrate_status_active_test',
    ];
  }

}
