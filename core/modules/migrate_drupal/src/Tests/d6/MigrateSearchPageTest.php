<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSearchPageTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;
use Drupal\Core\Database\Database;

/**
 * Upgrade search rank settings to search.page.*.yml.
 *
 * @group migrate_drupal
 */
class MigrateSearchPageTest extends MigrateDrupalTestBase {

  /**
   * The modules to be enabled during the test.
   *
   * @var array
   */
  static $modules = array('node', 'search');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_search_page');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6SearchPage.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests Drupal 6 search settings to Drupal 8 search page entity migration.
   */
  public function testSearchPage() {
    $id = 'node_search';
    /** @var \Drupal\search\Entity\SearchPage $search_page */
    $search_page = entity_load('search_page', $id);
    $this->assertEqual($search_page->id(), $id);
    $configuration = $search_page->getPlugin()->getConfiguration();
    $this->assertEqual($configuration['rankings'], array(
      'comments' => 5,
      'relevance' => 2,
      'sticky' => 8,
      'views' => 1,
    ));
    $this->assertEqual($search_page->getPath(), 'node');

    // Test that we can re-import using the EntitySearchPage destination.
    Database::getConnection('default', 'migrate')
      ->update('variable')
      ->fields(array('value' => serialize(4)))
      ->condition('name', 'node_rank_comments')
      ->execute();

    $migration = entity_load_unchanged('migration', 'd6_search_page');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();

    $search_page = entity_load('search_page', $id);
    $configuration = $search_page->getPlugin()->getConfiguration();
    $this->assertEqual($configuration['rankings']['comments'], 4);
  }

}
