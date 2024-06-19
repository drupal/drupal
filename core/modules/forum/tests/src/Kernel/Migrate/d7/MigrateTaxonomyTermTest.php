<?php

declare(strict_types=1);

namespace Drupal\Tests\forum\Kernel\Migrate\d7;

use Drupal\Tests\forum\Kernel\Migrate\MigrateTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Test migration of forum taxonomy terms.
 *
 * @group forum
 */
class MigrateTaxonomyTermTest extends MigrateDrupal7TestBase {

  use MigrateTestTrait;
  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'forum',
    'content_translation',
    'datetime',
    'datetime_range',
    'image',
    'language',
    'menu_ui',
    'node',
    'taxonomy',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('forum');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('file');

    $this->migrateTaxonomyTerms();
    $this->executeMigrations([
      'language',
      'd7_user_role',
      'd7_user',
      'd7_entity_translation_settings',
      'd7_taxonomy_term_entity_translation',
    ]);
  }

  /**
   * Gets the path to the fixture file.
   */
  protected function getFixtureFilePath() {
    return __DIR__ . '/../../../../fixtures/drupal7.php';
  }

  /**
   * Assert the forum taxonomy terms.
   */
  public function testTaxonomyTerms(): void {
    $this->assertEntity(1, 'en', 'General discussion', 'forums', '', NULL, 2, ['0'], 0);

    $this->assertEntity(5, 'en', 'Custom Forum', 'forums', 'Where the cool kids are.', NULL, 3, ['0'], 0);
    $this->assertEntity(6, 'en', 'Games', 'forums', NULL, '', 4, ['0'], 1);
    $this->assertEntity(7, 'en', 'Minecraft', 'forums', '', NULL, 1, [6], 0);
    $this->assertEntity(8, 'en', 'Half Life 3', 'forums', '', NULL, 0, [6], 0);

    // Verify that we still can create forum containers after the migration.
    $term = Term::create([
      'vid' => 'forums',
      'name' => 'Forum Container',
      'forum_container' => 1,
    ]);
    $term->save();

    // Reset the forums tree data so this new term is included in the tree.
    unset($this->treeData['forums']);
    $this->assertEntity(9, 'en', 'Forum Container', 'forums', '', '', 0, ['0'], 1);
  }

}
