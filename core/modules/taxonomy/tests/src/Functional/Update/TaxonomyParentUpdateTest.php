<?php

namespace Drupal\Tests\taxonomy\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Ensure that the taxonomy updates are running as expected.
 *
 * @group taxonomy
 * @group Update
 * @group legacy
 */
class TaxonomyParentUpdateTest extends UpdatePathTestBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->db = $this->container->get('database');
  }

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8-rc1.bare.standard.php.gz',
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.views-taxonomy-parent-2543726.php',
    ];
  }

  /**
   * Tests taxonomy term parents update.
   *
   * @see taxonomy_update_8501()
   * @see taxonomy_update_8502()
   * @see taxonomy_update_8503()
   */
  public function testTaxonomyUpdateParents() {
    // Run updates.
    $this->runUpdates();

    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = Term::load(1);
    $parents = [2, 3];
    $this->assertCount(2, $term->parent);
    $this->assertTrue(in_array($term->parent[0]->entity->id(), $parents));
    $this->assertTrue(in_array($term->parent[1]->entity->id(), $parents));

    $term = Term::load(2);
    $parents = [0, 3];
    $this->assertCount(2, $term->parent);
    $this->assertTrue(in_array($term->parent[0]->target_id, $parents));
    $this->assertTrue(in_array($term->parent[1]->target_id, $parents));

    $term = Term::load(3);
    $this->assertCount(1, $term->parent);
    // Target ID is returned as string.
    $this->assertSame(0, (int) $term->get('parent')[0]->target_id);

    // Test if the view has been converted to use the {taxonomy_term__parent}
    // table instead of the {taxonomy_term_hierarchy} table.
    $view = $this->config("views.view.test_taxonomy_parent");

    $relationship_base_path = 'display.default.display_options.relationships.parent';
    $this->assertSame('taxonomy_term__parent', $view->get("$relationship_base_path.table"));
    $this->assertSame('parent_target_id', $view->get("$relationship_base_path.field"));

    $filters_base_path_1 = 'display.default.display_options.filters.parent';
    $this->assertSame('taxonomy_term__parent', $view->get("$filters_base_path_1.table"));
    $this->assertSame('parent_target_id', $view->get("$filters_base_path_1.field"));

    $filters_base_path_2 = 'display.default.display_options.filters.parent';
    $this->assertSame('taxonomy_term__parent', $view->get("$filters_base_path_2.table"));
    $this->assertSame('parent_target_id', $view->get("$filters_base_path_2.field"));

    // The {taxonomy_term_hierarchy} table has been removed.
    $this->assertFalse($this->db->schema()->tableExists('taxonomy_term_hierarchy'));
  }

}
