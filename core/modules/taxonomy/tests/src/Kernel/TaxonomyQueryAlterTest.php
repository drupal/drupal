<?php

namespace Drupal\Tests\taxonomy\Kernel;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Tests that appropriate query tags are added.
 *
 * @group taxonomy
 */
class TaxonomyQueryAlterTest extends KernelTestBase {

  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'taxonomy',
    'taxonomy_test',
    'text',
    'user',
  ];

  /**
   * Tests that appropriate tags are added when querying the database.
   */
  public function testTaxonomyQueryAlter() {
    $this->installEntitySchema('taxonomy_term');

    // Create a new vocabulary and add a few terms to it.
    $vocabulary = $this->createVocabulary();
    $terms = [];
    for ($i = 0; $i < 5; $i++) {
      $terms[$i] = $this->createTerm($vocabulary);
    }

    // Set up hierarchy. Term 2 is a child of 1.
    $terms[2]->parent = $terms[1]->id();
    $terms[2]->save();

    /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
    $term_storage = $this->container->get('entity_type.manager')->getStorage('taxonomy_term');

    $this->setupQueryTagTestHooks();
    $loaded_term = $term_storage->load($terms[0]->id());
    // First term was loaded.
    $this->assertEquals($terms[0]->id(), $loaded_term->id());
    // TermStorage::load().
    $this->assertQueryTagTestResult(1, 0);

    $this->setupQueryTagTestHooks();
    $loaded_terms = $term_storage->loadTree($vocabulary->id());
    // All terms were loaded.
    $this->assertCount(5, $loaded_terms);
    // TermStorage::loadTree().
    $this->assertQueryTagTestResult(1, 1);

    $this->setupQueryTagTestHooks();
    $loaded_terms = $term_storage->loadParents($terms[2]->id());
    // All parent terms were loaded.
    $this->assertCount(1, $loaded_terms);
    // TermStorage::loadParents().
    $this->assertQueryTagTestResult(3, 1);

    $this->setupQueryTagTestHooks();
    $loaded_terms = $term_storage->loadChildren($terms[1]->id());
    // All child terms were loaded.
    $this->assertCount(1, $loaded_terms);
    // TermStorage::loadChildren().
    $this->assertQueryTagTestResult(3, 1);

    $this->setupQueryTagTestHooks();
    $connection = Database::getConnection();
    $query = $connection->select('taxonomy_term_data', 't');
    $query->addField('t', 'tid');
    $query->addTag('taxonomy_term_access');
    $tids = $query->execute()->fetchCol();
    // All term IDs were retrieved.
    $this->assertCount(5, $tids);
    // Database custom ::select() with 'taxonomy_term_access' tag (preferred).
    $this->assertQueryTagTestResult(1, 1);

    $this->setupQueryTagTestHooks();
    $query = $connection->select('taxonomy_term_data', 't');
    $query->addField('t', 'tid');
    $query->addTag('term_access');
    $tids = $query->execute()->fetchCol();
    // All term IDs were retrieved.
    $this->assertCount(5, $tids);
    // Database custom ::select() with term_access tag (deprecated).
    $this->assertQueryTagTestResult(1, 1);

    $this->setupQueryTagTestHooks();
    $query = \Drupal::entityQuery('taxonomy_term')->accessCheck(FALSE);
    $query->addTag('taxonomy_term_access');
    $result = $query->execute();
    // All term IDs were retrieved.
    $this->assertCount(5, $result);
    // Custom entity query with taxonomy_term_access tag (preferred).
    $this->assertQueryTagTestResult(1, 1);

    $this->setupQueryTagTestHooks();
    $query = \Drupal::entityQuery('taxonomy_term')->accessCheck(FALSE);
    $query->addTag('term_access');
    $result = $query->execute();
    // All term IDs were retrieved.
    $this->assertCount(5, $result);
    // Custom entity query with taxonomy_term_access tag (preferred).
    $this->assertQueryTagTestResult(1, 1);
  }

  /**
   * Sets up the hooks in the test module.
   */
  protected function setupQueryTagTestHooks() {
    taxonomy_terms_static_reset();
    $state = $this->container->get('state');
    $state->set('taxonomy_test_query_alter', 0);
    $state->set('taxonomy_test_query_term_access_alter', 0);
    $state->set('taxonomy_test_query_taxonomy_term_access_alter', 0);
  }

  /**
   * Verifies invocation of the hooks in the test module.
   *
   * @param int $expected_generic_invocations
   *   The number of times the generic query_alter hook is expected to have
   *   been invoked.
   * @param int $expected_specific_invocations
   *   The number of times the tag-specific query_alter hooks are expected to
   *   have been invoked.
   */
  protected function assertQueryTagTestResult($expected_generic_invocations, $expected_specific_invocations) {
    $state = $this->container->get('state');
    $this->assertEquals($expected_generic_invocations, $state->get('taxonomy_test_query_alter'));
    $this->assertEquals($expected_specific_invocations, $state->get('taxonomy_test_query_term_access_alter'));
    $this->assertEquals($expected_specific_invocations, $state->get('taxonomy_test_query_taxonomy_term_access_alter'));
  }

}
