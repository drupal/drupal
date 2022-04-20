<?php

namespace Drupal\Tests\aggregator\Functional\migrate_drupal_ui\d7;

use Drupal\Tests\migrate_drupal_ui\Functional\MultilingualReviewPageTestBase;

// cspell:ignore Filefield Flexslider Multiupload Imagefield

/**
 * Tests migrate upgrade review page for Drupal 7 for the aggregator module.
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
      'Block languages',
      'Blog',
      'Bulk Export',
      'Chaos tools',
      'Chaos Tools (CTools) AJAX Example',
      'Comment',
      'Contact',
      'Content translation',
      'Contextual links',
      'Custom content panes',
      'Custom rulesets',
      'Dashboard',
      'Database logging',
      'Date',
      'Date API',
      'Date All Day',
      'Date Context',
      'Date Migration',
      'Date Popup',
      'Date Repeat API',
      'Date Repeat Field',
      'Date Tools',
      'Date Views',
      'Email',
      'Entity API',
      'Entity Reference',
      'Entity Translation',
      'Entity feature module',
      'Entity tokens',
      'Field',
      'Field SQL storage',
      'Field UI',
      'File',
      'Filter',
      'Help',
      'Image',
      'Internationalization',
      'Link',
      'List',
      'Locale',
      'Menu',
      'Menu translation',
      'Multiupload Filefield Widget',
      'Multiupload Imagefield Widget',
      'Node',
      'Node Reference',
      'Number',
      'OpenID',
      'Options',
      'Overlay',
      'PHP filter',
      'Page manager',
      'Path',
      'Poll',
      'Profile',
      'RDF',
      'Search',
      'Search embedded form',
      'Shortcut',
      'String translation',
      'Stylizer',
      'Synchronize translations',
      'System',
      'Taxonomy translation',
      'Taxonomy',
      'Term Depth access',
      'Test search node tags',
      'Test search type',
      'Testing',
      'Text',
      'Title',
      'Toolbar',
      'Trigger',
      'User',
      'User Reference',
      'Views content panes',
      'Views UI',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths() {
    return [
      'Book',
      'Breakpoints',
      'Color',
      'Contact translation',
      'Entity Translation Menu',
      'Entity Translation Upgrade',
      'Field translation',
      // Flexslider_picture is a sub module of Picture module. Only the
      // styles from picture are migrated.
      'FlexSlider Picture',
      'Forum',
      'Multilingual content',
      'Multilingual forum',
      'Multilingual select',
      'Path translation',
      'Phone',
      'Picture',
      'References',
      'References UUID',
      'Statistics',
      'Syslog',
      'Telephone',
      'Tracker',
      'Translation redirect',
      'Translation sets',
      'Update manager',
      'User mail translation',
      'Variable',
      'Variable admin',
      'Variable realm',
      'Variable store',
      'Variable translation',
      'Variable views',
      'Views',
      'migrate_status_active_test',
    ];
  }

}
