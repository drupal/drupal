<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Migrate\d7\MigrateTaxonomyVocabularyTest.
 */

namespace Drupal\taxonomy\Tests\Migrate\d7;

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Migrate taxonomy vocabularies to taxonomy.vocabulary.*.yml.
 *
 * @group taxonomy
 */
class MigrateTaxonomyVocabularyTest extends MigrateDrupal7TestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d7_taxonomy_vocabulary');
  }

  /**
   * Validate a migrated vocabulary contains the expected values.
   *
   * @param $id
   *   Entity ID to load and check.
   * @param $expected_label
   *   The label the migrated entity should have.
   * @param $expected_description
   *   The description the migrated entity should have.
   * @param $expected_hierarchy
   *   The hierarchy setting the migrated entity should have.
   * @param $expected_weight
   *   The weight the migrated entity should have.
   */
  protected function assertEntity($id, $expected_label, $expected_description, $expected_hierarchy, $expected_weight) {
    /** @var \Drupal\taxonomy\VocabularyInterface $entity */
    $entity = Vocabulary::load($id);
    $this->assertTrue($entity instanceof VocabularyInterface);
    $this->assertIdentical($expected_label, $entity->label());
    $this->assertIdentical($expected_description, $entity->getDescription());
    $this->assertIdentical($expected_hierarchy, $entity->getHierarchy());
    $this->assertIdentical($expected_weight, $entity->get('weight'));
  }

  /**
   * Tests the Drupal 7 taxonomy vocabularies to Drupal 8 migration.
   */
  public function testTaxonomyVocabulary() {
    $this->assertEntity('tags', 'Tags', 'Use tags to group articles on similar topics into categories.', TAXONOMY_HIERARCHY_DISABLED, 0);
    $this->assertEntity('forums', 'Forums', 'Forum navigation vocabulary', TAXONOMY_HIERARCHY_SINGLE, -10);
    $this->assertEntity('test_vocabulary', 'Test Vocabulary', 'This is the vocabulary description', TAXONOMY_HIERARCHY_SINGLE, 0);
  }

}
