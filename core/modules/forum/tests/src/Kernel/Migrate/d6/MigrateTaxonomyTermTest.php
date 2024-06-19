<?php

declare(strict_types=1);

namespace Drupal\Tests\forum\Kernel\Migrate\d6;

use Drupal\Tests\forum\Kernel\Migrate\MigrateTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Test migration of forum taxonomy terms.
 *
 * @group forum
 */
class MigrateTaxonomyTermTest extends MigrateDrupal6TestBase {

  use MigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy', 'comment', 'forum', 'menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig('forum');
    $this->executeMigrations(['d6_taxonomy_vocabulary', 'd6_taxonomy_term']);
  }

  /**
   * Gets the path to the fixture file.
   */
  protected function getFixtureFilePath() {
    return __DIR__ . '/../../../../fixtures/drupal6.php';
  }

  /**
   * Assert the forum taxonomy terms.
   */
  public function testTaxonomyTerms(): void {
    $this->assertEntity(8, 'en', 'General discussion', 'forums', '', NULL, 2, ['0'], 0);
    $this->assertEntity(9, 'en', 'Earth', 'forums', '', NULL, 0, ['0'], 1);
    $this->assertEntity(10, 'en', 'Birds', 'forums', '', NULL, 0, ['9'], 0);
    $this->assertEntity(11, 'en', 'Oak', 'trees', '', NULL, 0, ['0'], NULL);
    $this->assertEntity(12, 'en', 'Ash', 'trees', '', NULL, 0, ['0'], NULL);
  }

}
