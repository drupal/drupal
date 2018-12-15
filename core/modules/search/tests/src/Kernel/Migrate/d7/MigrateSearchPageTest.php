<?php

namespace Drupal\Tests\search\Kernel\Migrate\d7;

use Drupal\Core\Database\Database;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\search\Entity\SearchPage;

/**
 * Tests migration of search page status and settings.
 *
 * @group migrate_drupal_7
 */
class MigrateSearchPageTest extends MigrateDrupal7TestBase {

  /**
   * The modules to be enabled during the test.
   *
   * {@inheritdoc}
   */
  public static $modules = ['search'];

  /**
   * Asserts various aspects of an SearchPage entity.
   *
   * @param string $id
   *   The expected search page ID.
   * @param string $path
   *   The expected path of the search page.
   * @param bool $status
   *   The expected status of the search page.
   * @param array $expected_config
   *   An array of expected configuration for the search page.
   */
  protected function assertEntity($id, $path, $status = FALSE, array $expected_config = NULL) {
    /** @var \Drupal\search\Entity\SearchPage $search_page */
    $search_page = SearchPage::load($id);
    $this->assertSame($id, $search_page->id());
    $this->assertSame($path, $search_page->getPath());
    $this->assertSame($status, $search_page->status());
    if (isset($expected_config)) {
      $configuration = $search_page->getPlugin()->getConfiguration();
      $this->assertSame($expected_config, $configuration);
    }
  }

  /**
   * Tests migration of search status and settings to search page entity.
   */
  public function testSearchPage() {
    $this->enableModules(['node']);
    $this->installConfig(['search']);
    $this->executeMigration('d7_search_page');
    $configuration = [
      'rankings' => [
        'comments' => 0,
        'promote' => 0,
        'relevance' => 2,
        'sticky' => 0,
        'views' => 0,
      ],
    ];
    $this->assertEntity('node_search', 'node', TRUE, $configuration);
    $this->assertEntity('user_search', 'user');

    // Test that we can re-import using the EntitySearchPage destination.
    Database::getConnection('default', 'migrate')
      ->update('variable')
      ->fields(['value' => serialize(4)])
      ->condition('name', 'node_rank_comments')
      ->execute();

    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->getMigration('d7_search_page');
    // Indicate we're rerunning a migration that's already run.
    $migration->getIdMap()->prepareUpdate();
    $this->executeMigration($migration);
    $configuration['rankings']['comments'] = 4;
    $this->assertEntity('node_search', 'node', TRUE, $configuration);
  }

  /**
   * Tests that search page is only migrated for modules enabled on D8 site.
   */
  public function testModuleExists() {
    $this->installConfig(['search']);
    $this->executeMigration('d7_search_page');

    $this->assertNull(SearchPage::load('node_search'));
    $this->assertEntity('user_search', 'user');
  }

  /**
   * Tests that a search page will be created if it does not exist.
   */
  public function testUserSearchCreate() {
    $this->enableModules(['node']);
    $this->installConfig(['search']);
    /** @var \Drupal\search\Entity\SearchPage $search_page */
    $search_page = SearchPage::load('user_search');
    $search_page->delete();
    $this->executeMigration('d7_search_page');

    $this->assertEntity('user_search', 'user');
  }

}
