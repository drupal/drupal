<?php

namespace Drupal\Tests\content_translation\Kernel\Migrate\d6;

use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\taxonomy\TermInterface;

/**
 * Test migration of translated taxonomy terms.
 *
 * @group migrate_drupal_6
 */
class MigrateTaxonomyTermTranslationTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'language',
    'menu_ui',
    'node',
    'taxonomy',
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
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(static::$modules);
    $this->executeMigrations([
      'd6_node_type',
      'd6_field',
      'd6_taxonomy_vocabulary',
      'd6_field_instance',
      'd6_taxonomy_term',
      'd6_taxonomy_term_translation',
    ]);
  }

  /**
   * Validate a migrated term contains the expected values.
   *
   * @param int $id
   *   Entity ID to load and check.
   * @param string $expected_language
   *   The language code for this term.
   * @param string $expected_label
   *   The label the migrated entity should have.
   * @param string $expected_vid
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
   *   The term reference ID the migrated entity field should have.
   */
  protected function assertEntity($id, $expected_language, $expected_label, $expected_vid, $expected_description = '', $expected_format = NULL, $expected_weight = 0, $expected_parents = [], $expected_field_integer_value = NULL, $expected_term_reference_tid = NULL) {
    /** @var \Drupal\taxonomy\TermInterface $entity */
    $entity = Term::load($id);
    $this->assertInstanceOf(TermInterface::class, $entity);
    $this->assertSame($expected_language, $entity->language()->getId());
    $this->assertSame($expected_label, $entity->label());
    $this->assertSame($expected_vid, $entity->bundle());
    $this->assertSame($expected_description, $entity->getDescription());
    $this->assertSame($expected_format, $entity->getFormat());
    $this->assertSame($expected_weight, $entity->getWeight());
    $this->assertHierarchy($expected_vid, $id, $expected_parents);
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
    // PostgreSQL, MySQL and SQLite may not return the parent terms in the same
    // order so sort before testing.
    sort($parent_ids);
    $actual_terms = array_filter($term->parents);
    sort($actual_terms);
    $this->assertEquals($parent_ids, $actual_terms, "Term $tid has correct parents in taxonomy tree");
  }

  /**
   * Tests the Drupal 6 i18n taxonomy term to Drupal 8 migration.
   */
  public function testTranslatedTaxonomyTerms() {
    $this->assertEntity(1, 'zu', 'zu - term 1 of vocabulary 1', 'vocabulary_1_i_0_', 'zu - description of term 1 of vocabulary 1', NULL, '0', []);
    $this->assertEntity(2, 'fr', 'fr - term 2 of vocabulary 2', 'vocabulary_2_i_1_', 'fr - description of term 2 of vocabulary 2', NULL, '3', []);
    $this->assertEntity(3, 'fr', 'fr - term 3 of vocabulary 2', 'vocabulary_2_i_1_', 'fr - description of term 3 of vocabulary 2', NULL, '4', ['2']);
    $this->assertEntity(4, 'en', 'term 4 of vocabulary 3', 'vocabulary_3_i_2_', 'description of term 4 of vocabulary 3', NULL, '6', []);
    $this->assertEntity(5, 'en', 'term 5 of vocabulary 3', 'vocabulary_3_i_2_', 'description of term 5 of vocabulary 3', NULL, '7', ['4']);
    $this->assertEntity(6, 'en', 'term 6 of vocabulary 3', 'vocabulary_3_i_2_', 'description of term 6 of vocabulary 3', NULL, '8', ['4', '5']);
    $this->assertEntity(7, 'fr', 'fr - term 2 of vocabulary 1', 'vocabulary_1_i_0_', 'fr - desc of term 2 vocab 1', NULL, '0', []);
  }

}
