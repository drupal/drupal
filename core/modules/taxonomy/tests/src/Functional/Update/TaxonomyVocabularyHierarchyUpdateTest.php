<?php

namespace Drupal\Tests\taxonomy\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests that the 'hierarchy' property is removed from vocabularies.
 *
 * @group taxonomy
 * @group Update
 * @group legacy
 */
class TaxonomyVocabularyHierarchyUpdateTest extends UpdatePathTestBase {

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

    // Run updates.
    $this->runUpdates();

    $hierarchy = \Drupal::config('taxonomy.vocabulary.test_vocabulary')->get('hierarchy');
    $this->assertNull($hierarchy);

    $database = \Drupal::database();
    $this->assertFalse($database->schema()->indexExists('taxonomy_term__parent', 'bundle'));
    $this->assertTrue($database->schema()->indexExists('taxonomy_term__parent', 'bundle_delta_target_id'));
  }

}
