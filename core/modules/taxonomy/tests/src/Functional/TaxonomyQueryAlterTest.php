<?php

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that appropriate query tags are added.
 *
 * @group taxonomy
 */
class TaxonomyQueryAlterTest extends BrowserTestBase {

  use TaxonomyTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['taxonomy', 'taxonomy_test'];

  /**
   * Tests that appropriate tags are added when querying the database.
   */
  public function testTaxonomyQueryAlter() {
    // Create a new vocabulary and add a few terms to it.
    $vocabulary = $this->createVocabulary();
    $terms = [];
    for ($i = 0; $i < 5; $i++) {
      $terms[$i] = $this->createTerm($vocabulary);
    }

    // Set up hierarchy. Term 2 is a child of 1.
    $terms[2]->parent = $terms[1]->id();
    $terms[2]->save();

    $term_storage = \Drupal::entityManager()->getStorage('taxonomy_term');

    $this->setupQueryTagTestHooks();
    $loaded_term = $term_storage->load($terms[0]->id());
    $this->assertEqual($loaded_term->id(), $terms[0]->id(), 'First term was loaded');
    $this->assertQueryTagTestResult(1, 0, 'TermStorage::load()');

    $this->setupQueryTagTestHooks();
    $loaded_terms = $term_storage->loadTree($vocabulary->id());
    $this->assertEqual(count($loaded_terms), count($terms), 'All terms were loaded');
    $this->assertQueryTagTestResult(1, 1, 'TermStorage::loadTree()');

    $this->setupQueryTagTestHooks();
    $loaded_terms = $term_storage->loadParents($terms[2]->id());
    $this->assertEqual(count($loaded_terms), 1, 'All parent terms were loaded');
    $this->assertQueryTagTestResult(3, 1, 'TermStorage::loadParents()');

    $this->setupQueryTagTestHooks();
    $loaded_terms = $term_storage->loadChildren($terms[1]->id());
    $this->assertEqual(count($loaded_terms), 1, 'All child terms were loaded');
    $this->assertQueryTagTestResult(3, 1, 'TermStorage::loadChildren()');

    $this->setupQueryTagTestHooks();
    $query = db_select('taxonomy_term_data', 't');
    $query->addField('t', 'tid');
    $query->addTag('taxonomy_term_access');
    $tids = $query->execute()->fetchCol();
    $this->assertEqual(count($tids), count($terms), 'All term IDs were retrieved');
    $this->assertQueryTagTestResult(1, 1, 'custom db_select() with taxonomy_term_access tag (preferred)');

    $this->setupQueryTagTestHooks();
    $query = db_select('taxonomy_term_data', 't');
    $query->addField('t', 'tid');
    $query->addTag('term_access');
    $tids = $query->execute()->fetchCol();
    $this->assertEqual(count($tids), count($terms), 'All term IDs were retrieved');
    $this->assertQueryTagTestResult(1, 1, 'custom db_select() with term_access tag (deprecated)');

    $this->setupQueryTagTestHooks();
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->addTag('taxonomy_term_access');
    $result = $query->execute();
    $this->assertEqual(count($result), count($terms), 'All term IDs were retrieved');
    $this->assertQueryTagTestResult(1, 1, 'custom EntityFieldQuery with taxonomy_term_access tag (preferred)');

    $this->setupQueryTagTestHooks();
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->addTag('term_access');
    $result = $query->execute();
    $this->assertEqual(count($result), count($terms), 'All term IDs were retrieved');
    $this->assertQueryTagTestResult(1, 1, 'custom EntityFieldQuery with term_access tag (deprecated)');
  }

  /**
   * Sets up the hooks in the test module.
   */
  protected function setupQueryTagTestHooks() {
    taxonomy_terms_static_reset();
    \Drupal::state()->set('taxonomy_test_query_alter', 0);
    \Drupal::state()->set('taxonomy_test_query_term_access_alter', 0);
    \Drupal::state()->set('taxonomy_test_query_taxonomy_term_access_alter', 0);
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
   * @param string $method
   *   A string describing the invoked function which generated the query.
   */
  protected function assertQueryTagTestResult($expected_generic_invocations, $expected_specific_invocations, $method) {
    $this->assertIdentical($expected_generic_invocations, \Drupal::state()->get('taxonomy_test_query_alter'), 'hook_query_alter() invoked when executing ' . $method);
    $this->assertIdentical($expected_specific_invocations, \Drupal::state()->get('taxonomy_test_query_term_access_alter'), 'Deprecated hook_query_term_access_alter() invoked when executing ' . $method);
    $this->assertIdentical($expected_specific_invocations, \Drupal::state()->get('taxonomy_test_query_taxonomy_term_access_alter'), 'Preferred hook_query_taxonomy_term_access_alter() invoked when executing ' . $method);
  }

}
