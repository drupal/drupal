<?php

/**
 * @file
 * Contains \Drupal\search\Tests\Migrate\d6\MigrateSearchPageTest.
 */

namespace Drupal\search\Tests\Migrate\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\Core\Database\Database;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade search rank settings to search.page.*.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateSearchPageTest extends MigrateDrupal6TestBase {

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
    $this->executeMigration('d6_search_page');
  }

  /**
   * Tests Drupal 6 search settings to Drupal 8 search page entity migration.
   */
  public function testSearchPage() {
    $id = 'node_search';
    /** @var \Drupal\search\Entity\SearchPage $search_page */
    $search_page = entity_load('search_page', $id);
    $this->assertIdentical($id, $search_page->id());
    $configuration = $search_page->getPlugin()->getConfiguration();
    $this->assertIdentical($configuration['rankings'], array(
      'comments' => 5,
      'relevance' => 2,
      'sticky' => 8,
      'views' => 1,
    ));
    $this->assertIdentical('node', $search_page->getPath());

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
    $this->assertIdentical(4, $configuration['rankings']['comments']);
  }

}
