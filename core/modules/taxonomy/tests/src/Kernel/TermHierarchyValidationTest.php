<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests handling of pending revisions.
 *
 * @coversDefaultClass \Drupal\taxonomy\Plugin\Validation\Constraint\TaxonomyTermHierarchyConstraintValidator
 *
 * @group taxonomy
 */
class TermHierarchyValidationTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('taxonomy_term');
  }

  /**
   * Tests the term hierarchy validation with re-parenting in pending revisions.
   */
  public function testTermHierarchyValidation(): void {
    $vocabulary_id = $this->randomMachineName();
    $vocabulary = Vocabulary::create([
      'name' => $vocabulary_id,
      'vid' => $vocabulary_id,
    ]);
    $vocabulary->save();

    // Create a simple hierarchy in the vocabulary, a root term and three parent
    // terms.
    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $term_storage */
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $root = $term_storage->create([
      'name' => $this->randomMachineName(),
      'vid' => $vocabulary_id,
    ]);
    $root->save();
    $parent1 = $term_storage->create([
      'name' => $this->randomMachineName(),
      'vid' => $vocabulary_id,
      'parent' => $root->id(),
    ]);
    $parent1->save();
    $parent2 = $term_storage->create([
      'name' => $this->randomMachineName(),
      'vid' => $vocabulary_id,
      'parent' => $root->id(),
    ]);
    $parent2->save();
    $parent3 = $term_storage->create([
      'name' => $this->randomMachineName(),
      'vid' => $vocabulary_id,
      'parent' => $root->id(),
    ]);
    $parent3->save();

    // Create a child term and assign one of the parents above.
    $child1 = $term_storage->create([
      'name' => $this->randomMachineName(),
      'vid' => $vocabulary_id,
      'parent' => $parent1->id(),
    ]);
    $violations = $child1->validate();
    $this->assertEmpty($violations);
    $child1->save();

    $validation_message = 'You can only change the hierarchy for the published version of this term.';

    // Add a pending revision without changing the term parent.
    $pending_name = $this->randomMachineName();
    $child_pending = $term_storage->createRevision($child1, FALSE);
    $child_pending->name = $pending_name;
    $violations = $child_pending->validate();
    $this->assertEmpty($violations);

    // Add a pending revision and change the parent.
    $child_pending = $term_storage->createRevision($child1, FALSE);
    $child_pending->parent = $parent2;
    $violations = $child_pending->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals($validation_message, $violations[0]->getMessage());
    $this->assertEquals('parent', $violations[0]->getPropertyPath());

    // Add a pending revision and add a new parent.
    $child_pending = $term_storage->createRevision($child1, FALSE);
    $child_pending->parent[0] = $parent1;
    $child_pending->parent[1] = $parent3;
    $violations = $child_pending->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals($validation_message, $violations[0]->getMessage());
    $this->assertEquals('parent', $violations[0]->getPropertyPath());

    // Add a pending revision and use the root term as a parent.
    $child_pending = $term_storage->createRevision($child1, FALSE);
    $child_pending->parent[0] = $root;
    $violations = $child_pending->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals($validation_message, $violations[0]->getMessage());
    $this->assertEquals('parent', $violations[0]->getPropertyPath());

    // Add a pending revision and remove the parent.
    $child_pending = $term_storage->createRevision($child1, FALSE);
    $child_pending->parent[0] = NULL;
    $violations = $child_pending->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals($validation_message, $violations[0]->getMessage());
    $this->assertEquals('parent', $violations[0]->getPropertyPath());

    // Add a pending revision and change the weight.
    $child_pending = $term_storage->createRevision($child1, FALSE);
    $child_pending->weight = 10;
    $violations = $child_pending->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals($validation_message, $violations[0]->getMessage());
    $this->assertEquals('weight', $violations[0]->getPropertyPath());

    // Add a pending revision and change both the parent and the weight.
    $child_pending = $term_storage->createRevision($child1, FALSE);
    $child_pending->parent = $parent2;
    $child_pending->weight = 10;
    $violations = $child_pending->validate();
    $this->assertCount(2, $violations);
    $this->assertEquals($validation_message, $violations[0]->getMessage());
    $this->assertEquals($validation_message, $violations[1]->getMessage());
    $this->assertEquals('parent', $violations[0]->getPropertyPath());
    $this->assertEquals('weight', $violations[1]->getPropertyPath());

    // Add a published revision and change the parent.
    $child_pending = $term_storage->createRevision($child1, TRUE);
    $child_pending->parent[0] = $parent2;
    $violations = $child_pending->validate();
    $this->assertEmpty($violations);

    // Add a new term as a third-level child.
    // The taxonomy tree structure ends up as follows:
    // root
    // - parent1
    // - parent2
    // -- child1 <- this will be a term with a pending revision
    // --- child2
    // - parent3
    $child2 = $term_storage->create([
      'name' => $this->randomMachineName(),
      'vid' => $vocabulary_id,
      'parent' => $child1->id(),
    ]);
    $child2->save();

    // Change 'child1' to be a pending revision.
    $child1 = $term_storage->createRevision($child1, FALSE);
    $child1->save();

    // Check that a child of a pending term can not be re-parented.
    $child2_pending = $term_storage->createRevision($child2, FALSE);
    $child2_pending->parent = $parent3;
    $violations = $child2_pending->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals($validation_message, $violations[0]->getMessage());
    $this->assertEquals('parent', $violations[0]->getPropertyPath());

    // Check that another term which has a pending revision can not moved under
    // another term which has pending revision.
    $parent3_pending = $term_storage->createRevision($parent3, FALSE);
    $parent3_pending->parent = $child1;
    $violations = $parent3_pending->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals($validation_message, $violations[0]->getMessage());
    $this->assertEquals('parent', $violations[0]->getPropertyPath());

    // Check that a new term can be created under a term that has a pending
    // revision.
    $child3 = $term_storage->create([
      'name' => $this->randomMachineName(),
      'vid' => $vocabulary_id,
      'parent' => $child1->id(),
    ]);
    $violations = $child3->validate();
    $this->assertEmpty($violations);
  }

}
