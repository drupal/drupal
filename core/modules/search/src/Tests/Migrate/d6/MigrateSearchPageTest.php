<?php

/**
 * @file
 * Contains \Drupal\search\Tests\Migrate\d6\MigrateSearchPageTest.
 */

namespace Drupal\search\Tests\Migrate\d6;

use Drupal\Core\Database\Database;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;
use Drupal\search\Entity\SearchPage;

/**
 * Upgrade search rank settings to search.page.*.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateSearchPageTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['search'];

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
    $search_page = SearchPage::load($id);
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

    /** @var \Drupal\migrate\Entity\MigrationInterface $migration */
    $migration = \Drupal::entityManager()
      ->getStorage('migration')
      ->loadUnchanged('d6_search_page');
    // Indicate we're rerunning a migration that's already run.
    $migration->getIdMap()->prepareUpdate();
    $this->executeMigration($migration);

    $configuration = SearchPage::load($id)->getPlugin()->getConfiguration();
    $this->assertIdentical(4, $configuration['rankings']['comments']);
  }

}
