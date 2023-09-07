<?php

namespace Drupal\Tests\forum\Kernel\Migrate;

use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;

/**
 * Common assertions for migration tests.
 */
trait MigrateTestTrait {

  /**
   * The cached taxonomy tree items, keyed by vid and tid.
   *
   * @var array
   */
  protected $treeData = [];

  /**
   * Validate a migrated term contains the expected values.
   *
   * @param int $id
   *   Entity ID to load and check.
   * @param string $expected_language
   *   The language code for this term.
   * @param $expected_label
   *   The label the migrated entity should have.
   * @param $expected_vid
   *   The parent vocabulary the migrated entity should have.
   * @param string|null $expected_description
   *   The description the migrated entity should have.
   * @param string|null $expected_format
   *   The format the migrated entity should have.
   * @param int $expected_weight
   *   The weight the migrated entity should have.
   * @param array $expected_parents
   *   The parent terms the migrated entity should have.
   * @param int|null $expected_container_flag
   *   The term should be a container entity.
   *
   * @internal
   */
  protected function assertEntity(int $id, string $expected_language, string $expected_label, string $expected_vid, ?string $expected_description = '', ?string $expected_format = NULL, int $expected_weight = 0, array $expected_parents = [], int|null $expected_container_flag = NULL): void {
    /** @var \Drupal\taxonomy\TermInterface $entity */
    $entity = Term::load($id);
    $this->assertInstanceOf(TermInterface::class, $entity);
    $this->assertSame($expected_language, $entity->language()->getId());
    $this->assertEquals($expected_label, $entity->label());
    $this->assertEquals($expected_vid, $entity->bundle());
    $this->assertEquals($expected_description, $entity->getDescription());
    $this->assertEquals($expected_format, $entity->getFormat());
    $this->assertEquals($expected_weight, $entity->getWeight());
    $this->assertEquals($expected_parents, array_column($entity->get('parent')
      ->getValue(), 'target_id'));
    $this->assertHierarchy($expected_vid, $id, $expected_parents);
    if (isset($expected_container_flag)) {
      $this->assertEquals($expected_container_flag, $entity->forum_container->value);
    }
  }

  /**
   * Retrieves the parent term IDs for a given term.
   *
   * @param $tid
   *   ID of the term to check.
   *
   * @return array
   *   List of parent term IDs.
   */
  protected function getParentIDs($tid) {
    return array_keys(\Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadParents($tid));
  }

  /**
   * Assert that a term is present in the tree storage, with the right parents.
   *
   * @param string $vid
   *   Vocabulary ID.
   * @param int $tid
   *   ID of the term to check.
   * @param array $parent_ids
   *   The expected parent term IDs.
   */
  protected function assertHierarchy(string $vid, int $tid, array $parent_ids): void {
    if (!isset($this->treeData[$vid])) {
      $tree = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadTree($vid);
      $this->treeData[$vid] = [];
      foreach ($tree as $item) {
        $this->treeData[$vid][$item->tid] = $item;
      }
    }

    $this->assertArrayHasKey($tid, $this->treeData[$vid], "Term $tid exists in taxonomy tree");
    $term = $this->treeData[$vid][$tid];
    $this->assertEquals($parent_ids, $term->parents, "Term $tid has correct parents in taxonomy tree");
  }

}
