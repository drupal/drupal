<?php

namespace Drupal\Tests\taxonomy\Kernel;

use Drupal\taxonomy\Entity\Term;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Kernel tests for taxonomy term functions.
 *
 * @group taxonomy
 */
class TermKernelTest extends KernelTestBase {

  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter', 'taxonomy', 'text', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['filter']);
    $this->installEntitySchema('taxonomy_term');
  }

  /**
   * Tests that a deleted term is no longer in the vocabulary.
   */
  public function testTermDelete() {
    $vocabulary = $this->createVocabulary();
    $valid_term = $this->createTerm($vocabulary);
    // Delete a valid term.
    $valid_term->delete();
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => $vocabulary->id()]);
    $this->assertTrue(empty($terms), 'Vocabulary is empty after deletion');
  }

  /**
   * Deleting a parent of a term with multiple parents does not delete the term.
   */
  public function testMultipleParentDelete() {
    $vocabulary = $this->createVocabulary();
    $parent_term1 = $this->createTerm($vocabulary);
    $parent_term2 = $this->createTerm($vocabulary);
    $child_term = $this->createTerm($vocabulary);
    $child_term->parent = [$parent_term1->id(), $parent_term2->id()];
    $child_term->save();
    $child_term_id = $child_term->id();

    $parent_term1->delete();
    $term_storage = $this->container->get('entity_type.manager')->getStorage('taxonomy_term');
    $term_storage->resetCache([$child_term_id]);
    $child_term = Term::load($child_term_id);
    $this->assertTrue(!empty($child_term), 'Child term is not deleted if only one of its parents is removed.');

    $parent_term2->delete();
    $term_storage->resetCache([$child_term_id]);
    $child_term = Term::load($child_term_id);
    $this->assertTrue(empty($child_term), 'Child term is deleted if all of its parents are removed.');
  }

  /**
   * Tests a taxonomy with terms that have multiple parents of different depths.
   */
  public function testTaxonomyVocabularyTree() {
    // Create a new vocabulary with 6 terms.
    $vocabulary = $this->createVocabulary();
    $term = [];
    for ($i = 0; $i < 6; $i++) {
      $term[$i] = $this->createTerm($vocabulary);
    }

    // Get the taxonomy storage.
    $taxonomy_storage = $this->container->get('entity_type.manager')->getStorage('taxonomy_term');

    // Set the weight on $term[1] so it appears before $term[5] when fetching
    // the parents for $term[2], in order to test for a regression on
    // \Drupal\taxonomy\TermStorageInterface::loadAllParents().
    $term[1]->weight = -1;
    $term[1]->save();

    // $term[2] is a child of 1 and 5.
    $term[2]->parent = [$term[1]->id(), $term[5]->id()];
    $term[2]->save();
    // $term[3] is a child of 2.
    $term[3]->parent = [$term[2]->id()];
    $term[3]->save();
    // $term[5] is a child of 4.
    $term[5]->parent = [$term[4]->id()];
    $term[5]->save();

    /**
     * Expected tree:
     * term[0] | depth: 0
     * term[1] | depth: 0
     * -- term[2] | depth: 1
     * ---- term[3] | depth: 2
     * term[4] | depth: 0
     * -- term[5] | depth: 1
     * ---- term[2] | depth: 2
     * ------ term[3] | depth: 3
     */
    // Count $term[1] parents with $max_depth = 1.
    $tree = $taxonomy_storage->loadTree($vocabulary->id(), $term[1]->id(), 1);
    $this->assertCount(1, $tree, 'We have one parent with depth 1.');

    // Count all vocabulary tree elements.
    $tree = $taxonomy_storage->loadTree($vocabulary->id());
    $this->assertCount(8, $tree, 'We have all vocabulary tree elements.');

    // Count elements in every tree depth.
    foreach ($tree as $element) {
      if (!isset($depth_count[$element->depth])) {
        $depth_count[$element->depth] = 0;
      }
      $depth_count[$element->depth]++;
    }
    $this->assertEquals(3, $depth_count[0], 'Three elements in taxonomy tree depth 0.');
    $this->assertEquals(2, $depth_count[1], 'Two elements in taxonomy tree depth 1.');
    $this->assertEquals(2, $depth_count[2], 'Two elements in taxonomy tree depth 2.');
    $this->assertEquals(1, $depth_count[3], 'One element in taxonomy tree depth 3.');

    /** @var \Drupal\taxonomy\TermStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    // Count parents of $term[2].
    $parents = $storage->loadParents($term[2]->id());
    $this->assertCount(2, $parents, 'The term has two parents.');

    // Count parents of $term[3].
    $parents = $storage->loadParents($term[3]->id());
    $this->assertCount(1, $parents, 'The term has one parent.');

    // Identify all ancestors of $term[2].
    $ancestors = $storage->loadAllParents($term[2]->id());
    $this->assertCount(4, $ancestors, 'The term has four ancestors including the term itself.');

    // Identify all ancestors of $term[3].
    $ancestors = $storage->loadAllParents($term[3]->id());
    $this->assertCount(5, $ancestors, 'The term has five ancestors including the term itself.');
  }

  /**
   * Tests that a Term is renderable when unsaved (preview).
   */
  public function testTermPreview() {
    $entity_manager = \Drupal::entityTypeManager();
    $vocabulary = $this->createVocabulary();

    // Create a unsaved term.
    $term = $entity_manager->getStorage('taxonomy_term')->create([
      'vid' => $vocabulary->id(),
      'name' => 'Inator',
    ]);

    // Confirm we can get the view of unsaved term.
    $render_array = $entity_manager->getViewBuilder('taxonomy_term')
      ->view($term);
    $this->assertTrue(!empty($render_array), 'Term view builder is built.');

    // Confirm we can render said view.
    $rendered = \Drupal::service('renderer')->renderPlain($render_array);
    $this->assertTrue(!empty(trim($rendered)), 'Term is able to be rendered.');
  }

}
