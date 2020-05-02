<?php

namespace Drupal\Tests\taxonomy\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Kernel tests for taxonomy pending revisions.
 *
 * @group taxonomy
 */
class PendingRevisionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'taxonomy',
    'node',
    'user',
    'text',
    'field',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('node', 'node_access');
  }

  /**
   * Tests that the taxonomy index work correctly with pending revisions.
   */
  public function testTaxonomyIndexWithPendingRevision() {
    \Drupal::configFactory()->getEditable('taxonomy.settings')->set('maintain_index_table', TRUE)->save();

    Vocabulary::create([
      'name' => 'test',
      'vid' => 'test',
    ])->save();
    $term = Term::create([
      'name' => 'term1',
      'vid' => 'test',
    ]);
    $term->save();
    $term2 = Term::create([
      'name' => 'term2',
      'vid' => 'test',
    ]);
    $term2->save();

    NodeType::create([
      'type' => 'page',
    ])->save();

    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_tags',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_tags',
      'entity_type' => 'node',
      'bundle' => 'page',
    ])->save();
    $node = Node::create([
      'type' => 'page',
      'title' => 'test_title',
      'field_tags' => [$term->id()],
    ]);
    $node->save();

    $taxonomy_index = $this->getTaxonomyIndex();
    $this->assertEquals($term->id(), $taxonomy_index[$node->id()]->tid);

    // Normal new revision.
    $node->setNewRevision(TRUE);
    $node->isDefaultRevision(TRUE);
    $node->field_tags->target_id = $term2->id();
    $node->save();

    $taxonomy_index = $this->getTaxonomyIndex();
    $this->assertEquals($term2->id(), $taxonomy_index[$node->id()]->tid);

    // Check that saving a pending revision does not affect the taxonomy index.
    $node->setNewRevision(TRUE);
    $node->isDefaultRevision(FALSE);
    $node->field_tags->target_id = $term->id();
    $node->save();

    $taxonomy_index = $this->getTaxonomyIndex();
    $this->assertEquals($term2->id(), $taxonomy_index[$node->id()]->tid);

    // Check that making the previously created pending revision the default
    // revision updates the taxonomy index correctly.
    $node->isDefaultRevision(TRUE);
    $node->save();

    $taxonomy_index = $this->getTaxonomyIndex();
    $this->assertEquals($term->id(), $taxonomy_index[$node->id()]->tid);
  }

  protected function getTaxonomyIndex() {
    return \Drupal::database()->select('taxonomy_index')
      ->fields('taxonomy_index')
      ->execute()
      ->fetchAllAssoc('nid');
  }

}
