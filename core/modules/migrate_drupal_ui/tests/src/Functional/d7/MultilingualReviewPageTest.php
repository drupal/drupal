<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional\d7;

use Drupal\Tests\migrate_drupal_ui\Functional\MultilingualReviewPageTestBase;

// cspell:ignore Filefield Flexslider Multiupload Imagefield

/**
 * Tests migrate upgrade review page for Drupal 7.
 *
 * Tests with translation modules enabled.
 *
 * @group migrate_drupal_7
 * @group migrate_drupal_ui
 */
class MultilingualReviewPageTest extends MultilingualReviewPageTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'datetime_range',
    'language',
    'content_translation',
    'telephone',
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
    $this->loadFixture($this->getModulePath('migrate_drupal') . '/tests/fixtures/drupal7.php');

    // @todo Remove this in https://www.drupal.org/node/3267515
    \Drupal::service('module_installer')->uninstall(['rdf']);
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
      'Block languages',
      'Blog',
      'Book',
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
      'Forum',
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
      'Overlay',
      'PHP filter',
      'Page manager',
      'Path',
      'Phone',
      'Poll',
      'Profile',
      'Search',
      'Search embedded form',
      'Shortcut',
      'Statistics',
      'String translation',
      'Stylizer',
      'Synchronize translations',
      'Syslog',
      'System',
      'Taxonomy translation',
      'Taxonomy',
      'Telephone',
      'Term Depth access',
      'Test search node tags',
      'Test search type',
      'Testing',
      'Text',
      'Title',
      'Toolbar',
      'Tracker',
      'Trigger',
      'Update manager',
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
      // Action is set not_finished in migrate_state_not_finished_test.
      'Aggregator',
      // Block is set not_finished in migrate_state_not_finished_test.
      'Block',
      'Breakpoints',
      // @todo Remove Color in https://www.drupal.org/project/drupal/issues/3270899
      'Color',
      'Contact translation',
      'Entity Translation Menu',
      'Entity Translation Upgrade',
      'Field translation',
      // Flexslider_picture is a sub module of Picture module. Only the
      // styles from picture are migrated.
      'FlexSlider Picture',
      'Multilingual content',
      'Multilingual forum',
      'Multilingual select',
      // Options is set not_finished in migrate_state_not_finished_test.
      'Options',
      'Path translation',
      'Picture',
      'RDF',
      'References',
      'References UUID',
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
    ];
  }

}
