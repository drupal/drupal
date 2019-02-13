<?php

namespace Drupal\Tests\taxonomy\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\Tests\Core\Database\SchemaIntrospectionTestTrait;

/**
 * Tests that the 'hierarchy' property is removed from vocabularies.
 *
 * @group taxonomy
 * @group Update
 * @group legacy
 */
class TaxonomyVocabularyHierarchyUpdateTest extends UpdatePathTestBase {

  use SchemaIntrospectionTestTrait;

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8-rc1.filled.standard.php.gz',
    ];
  }

  /**
   * Tests that the 'hierarchy' property is removed from vocabularies.
   *
   * @see taxonomy_post_update_remove_hierarchy_from_vocabularies()
   * @see taxonomy_update_8701()
   */
  public function testTaxonomyUpdateParents() {
    $hierarchy = \Drupal::config('taxonomy.vocabulary.test_vocabulary')->get('hierarchy');
    $this->assertSame(1, $hierarchy);

    // We can not test whether an index on the 'bundle' column existed before
    // running the updates because the 'taxonomy_term__parent' table itself is
    // created by an update function.

    // Run updates.
    $this->runUpdates();

    $hierarchy = \Drupal::config('taxonomy.vocabulary.test_vocabulary')->get('hierarchy');
    $this->assertNull($hierarchy);

    $this->assertNoIndexOnColumns('taxonomy_term__parent', ['bundle']);
    $this->assertIndexOnColumns('taxonomy_term__parent', ['bundle', 'delta', 'parent_target_id']);
  }

}
