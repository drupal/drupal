<?php

namespace Drupal\Tests\aggregator\Functional\migrate_drupal_ui\d7;

use Drupal\Tests\migrate_drupal_ui\Functional\NoMultilingualReviewPageTestBase;

// cspell:ignore Filefield Multiupload Imagefield

/**
 * Tests Drupal 7 upgrade without translations for the aggregator module.
 *
 * The test method is provided by the MigrateUpgradeTestBase class.
 *
 * @group aggregator
 * @group legacy
 */
class NoMultilingualReviewPageTest extends NoMultilingualReviewPageTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'aggregator',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loadFixture($this->getModulePath('aggregator') . '/tests/fixtures/drupal7.php');
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
      'Bulk Export',
      'Chaos Tools (CTools) AJAX Example',
      'Chaos tools',
      'Comment',
      'Contact',
      'Custom content panes',
      'Custom rulesets',
      'Dashboard',
      'Database logging',
      'Date',
      'Date All Day',
      'Date Context',
      'Date Migration',
      'Date Popup',
      'Date Repeat API',
      'Date Repeat Field',
      'Date Tools',
      'Date Views',
      'Email',
      'Entity Reference',
      'Entity feature module',
      'Entity tokens',
      'Field',
      'Field SQL storage',
      'File',
      'Filter',
      'Image',
      'Link',
      'List',
      'Menu',
      'Multiupload Filefield Widget',
      'Multiupload Imagefield Widget',
      'Node',
      'Node Reference',
      'Number',
      'OpenID',
      'Options',
      'Overlay',
      'Page manager',
      'Path',
      'Poll',
      'Profile',
      'RDF',
      'Search',
      'Search embedded form',
      'Shortcut',
      'Stylizer',
      'Synchronize translations',
      'System',
      'Taxonomy',
      'Term Depth access',
      'Test search node tags',
      'Test search type',
      'Text',
      'Title',
      'User',
      'User Reference',
      'Views UI',
      'Views content panes',
      // Include modules that do not have an upgrade path and are enabled in the
      // source database.
      'Blog',
      'Content translation',
      'Contextual links',
      'Date API',
      'Entity API',
      'Field UI',
      'Help',
      'PHP filter',
      'Testing',
      'Toolbar',
      'Trigger',
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
      'Block languages',
      'Book',
      'Breakpoints',
      'Color',
      'Contact translation',
      'Entity Translation',
      'Entity Translation Menu',
      'Entity Translation Upgrade',
      'Field translation',
      'FlexSlider Picture',
      'Forum',
      'Internationalization',
      'Locale',
      'Menu translation',
      'Multilingual content',
      'Multilingual forum',
      'Multilingual select',
      'Path translation',
      'Phone',
      'Picture',
      'References',
      'References UUID',
      'Statistics',
      'String translation',
      'Taxonomy translation',
      'Telephone',
      'Translation redirect',
      'Translation sets',
      'User mail translation',
      'Variable',
      'Variable admin',
      'Variable realm',
      'Variable store',
      'Variable translation',
      'Variable views',
      'Views',
      'migrate_status_active_test',
      // These modules are in the missing path list because they are installed
      // on the source site but they are not installed on the destination site.
      'Syslog',
      'Tracker',
      'Update manager',
    ];
  }

}
