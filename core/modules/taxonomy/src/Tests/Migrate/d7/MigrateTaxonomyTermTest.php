<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Migrate\d7\MigrateTaxonomyTermTest.
 */

namespace Drupal\taxonomy\Tests\Migrate\d7;

use Drupal\taxonomy\Entity\Term;
use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;
use Drupal\taxonomy\TermInterface;

/**
 * Upgrade taxonomy terms.
 *
 * @group taxonomy
 */
class MigrateTaxonomyTermTest extends MigrateDrupal7TestBase {

  static $modules = array('taxonomy', 'text');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('taxonomy_term');
    $this->executeMigration('d7_taxonomy_vocabulary');
    $this->executeMigration('d7_taxonomy_term');
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
   * @param int $expected_weight
   *   The weight the migrated entity should have.
   * @param array $expected_parents
   *   The parent terms the migrated entity should have.
   */
  protected function assertEntity($id, $expected_label, $expected_vid, $expected_description = '', $expected_weight = 0, $expected_parents = []) {
    /** @var \Drupal\taxonomy\TermInterface $entity */
    $entity = Term::load($id);
    $this->assertTrue($entity instanceof TermInterface);
    $this->assertIdentical($expected_label, $entity->label());
    $this->assertIdentical($expected_vid, $entity->getVocabularyId());
    $this->assertEqual($expected_description, $entity->getDescription());
    $this->assertEqual($expected_weight, $entity->getWeight());
    $this->assertIdentical($expected_parents, $this->getParentIDs($id));
  }

  /**
   * Tests the Drupal 7 taxonomy term to Drupal 8 migration.
   */
  public function testTaxonomyTerms() {
    $this->assertEntity(1, 'General discussion', 'forums', '', 2);
    $this->assertEntity(2, 'Term1', 'test_vocabulary', 'The first term.');
    $this->assertEntity(3, 'Term2', 'test_vocabulary', 'The second term.');
    $this->assertEntity(4, 'Term3', 'test_vocabulary', 'The third term.', 0, [3]);
    $this->assertEntity(5, 'Custom Forum', 'forums', 'Where the cool kids are.', 3);
    $this->assertEntity(6, 'Games', 'forums', '', 4);
    $this->assertEntity(7, 'Minecraft', 'forums', '', 1, [6]);
    $this->assertEntity(8, 'Half Life 3', 'forums', '', 0, [6]);
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

}
