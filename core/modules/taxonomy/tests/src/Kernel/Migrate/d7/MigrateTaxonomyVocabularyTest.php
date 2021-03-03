<?php

namespace Drupal\Tests\taxonomy\Kernel\Migrate\d7;

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Migrate taxonomy vocabularies to taxonomy.vocabulary.*.yml.
 *
 * @group taxonomy
 */
class MigrateTaxonomyVocabularyTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy', 'text'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
   * @param $expected_weight
   *   The weight the migrated entity should have.
   */
  protected function assertEntity($id, $expected_label, $expected_description, $expected_weight) {
    /** @var \Drupal\taxonomy\VocabularyInterface $entity */
    $entity = Vocabulary::load($id);
    $this->assertInstanceOf(VocabularyInterface::class, $entity);
    $this->assertSame($expected_label, $entity->label());
    $this->assertSame($expected_description, $entity->getDescription());
    $this->assertSame($expected_weight, $entity->get('weight'));
  }

  /**
   * Tests the Drupal 7 taxonomy vocabularies to Drupal 8 migration.
   */
  public function testTaxonomyVocabulary() {
    $this->assertEntity('tags', 'Tags', 'Use tags to group articles on similar topics into categories.', 0);
    $this->assertEntity('forums', 'Sujet de discussion', 'Forum navigation vocabulary', -10);
    $this->assertEntity('test_vocabulary', 'Test Vocabulary', 'This is the vocabulary description', 0);
    $this->assertEntity('vocabulary_name_much_longer_th', 'vocabulary name clearly different than machine name and much longer than thirty two characters', 'description of vocabulary name much longer than thirty two characters', 0);
  }

}
