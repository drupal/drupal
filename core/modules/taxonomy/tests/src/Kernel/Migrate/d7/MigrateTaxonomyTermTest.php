<?php

namespace Drupal\Tests\taxonomy\Kernel\Migrate\d7;

use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\taxonomy\TermInterface;

/**
 * Upgrade taxonomy terms.
 *
 * @group taxonomy
 */
class MigrateTaxonomyTermTest extends MigrateDrupal7TestBase {

  public static $modules = [
    'comment',
    'datetime',
    'image',
    'link',
    'menu_ui',
    'node',
    'taxonomy',
    'telephone',
    'text',
  ];

  /**
   * The cached taxonomy tree items, keyed by vid and tid.
   *
   * @var array
   */
  protected $treeData = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(static::$modules);

    $this->executeMigrations([
      'd7_node_type',
      'd7_comment_type',
      'd7_field',
      'd7_taxonomy_vocabulary',
      'd7_field_instance',
      'd7_taxonomy_term'
    ]);
  }

  /**
   * Validate a migrated term contains the expected values.
   *
   * @param $id
   *   Entity ID to load and check.
   * @param $expected_label
   *   The label the migrated entity should have.
   * @param $expected_vid
   *   The parent vocabulary the migrated entity should have.
   * @param string $expected_description
   *   The description the migrated entity should have.
   * @param string $expected_format
   *   The format the migrated entity should have.
   * @param int $expected_weight
   *   The weight the migrated entity should have.
   * @param array $expected_parents
   *   The parent terms the migrated entity should have.
   * @param int $expected_field_integer_value
   *   The value the migrated entity field should have.
   * @param int $expected_term_reference_tid
   *   The term reference id the migrated entity field should have.
   */
  protected function assertEntity($id, $expected_label, $expected_vid, $expected_description = '', $expected_format = NULL, $expected_weight = 0, $expected_parents = [], $expected_field_integer_value = NULL, $expected_term_reference_tid = NULL) {
    /** @var \Drupal\taxonomy\TermInterface $entity */
    $entity = Term::load($id);
    $this->assertTrue($entity instanceof TermInterface);
    $this->assertIdentical($expected_label, $entity->label());
    $this->assertIdentical($expected_vid, $entity->bundle());
    $this->assertEqual($expected_description, $entity->getDescription());
    $this->assertEquals($expected_format, $entity->getFormat());
    $this->assertEqual($expected_weight, $entity->getWeight());
    $this->assertIdentical($expected_parents, $this->getParentIDs($id));
    $this->assertHierarchy($expected_vid, $id, $expected_parents);
    if (!is_null($expected_field_integer_value)) {
      $this->assertTrue($entity->hasField('field_integer'));
      $this->assertEquals($expected_field_integer_value, $entity->field_integer->value);
    }
    if (!is_null($expected_term_reference_tid)) {
      $this->assertTrue($entity->hasField('field_integer'));
      $this->assertEquals($expected_term_reference_tid, $entity->field_term_reference->target_id);
    }
  }

  /**
   * Tests the Drupal 7 taxonomy term to Drupal 8 migration.
   */
  public function testTaxonomyTerms() {
    $this->assertEntity(1, 'General discussion', 'forums', '', NULL, 2);
    $this->assertEntity(2, 'Term1', 'test_vocabulary', 'The first term.', 'filtered_html', 0, [], NULL, 3);
    $this->assertEntity(3, 'Term2', 'test_vocabulary', 'The second term.', 'filtered_html');
    $this->assertEntity(4, 'Term3', 'test_vocabulary', 'The third term.', 'full_html', 0, [3], 6);
    $this->assertEntity(5, 'Custom Forum', 'forums', 'Where the cool kids are.', NULL, 3);
    $this->assertEntity(6, 'Games', 'forums', '', NULL, 4);
    $this->assertEntity(7, 'Minecraft', 'forums', '', NULL, 1, [6]);
    $this->assertEntity(8, 'Half Life 3', 'forums', '', NULL, 0, [6]);
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
    return array_keys(\Drupal::entityManager()->getStorage('taxonomy_term')->loadParents($tid));
  }

  /**
   * Assert that a term is present in the tree storage, with the right parents.
   *
   * @param string $vid
   *   Vocabular ID.
   * @param int $tid
   *   ID of the term to check.
   * @param array $parent_ids
   *   The expected parent term IDs.
   */
  protected function assertHierarchy($vid, $tid, array $parent_ids) {
    if (!isset($this->treeData[$vid])) {
      $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
      $this->treeData[$vid] = [];
      foreach ($tree as $item) {
        $this->treeData[$vid][$item->tid] = $item;
      }
    }

    $this->assertArrayHasKey($tid, $this->treeData[$vid], "Term $tid exists in taxonomy tree");
    $term = $this->treeData[$vid][$tid];
    $this->assertEquals($parent_ids, array_filter($term->parents), "Term $tid has correct parents in taxonomy tree");
  }

}
