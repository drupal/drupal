<?php

namespace Drupal\Tests\taxonomy\Kernel\Migrate\d7;

use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\taxonomy\TermInterface;

/**
 * Test migration of translated taxonomy terms.
 *
 * @group migrate_drupal_7
 */
class MigrateTaxonomyTermLanguageTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'comment',
    'datetime',
    'image',
    'language',
    'link',
    'menu_ui',
    // Required for translation migrations.
    'migrate_drupal_multilingual',
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
    $this->installEntitySchema('comment');
    $this->installEntitySchema('file');

    $this->migrateTaxonomyTerms();
    $this->executeMigrations([
      'language',
      'd7_user_role',
      'd7_user',
      'd7_taxonomy_term_language',
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
  protected function assertEntity($id, $expected_language, $expected_label, $expected_vid, $expected_description = '', $expected_format = NULL, $expected_weight = 0, array $expected_parents = [], $expected_field_integer_value = NULL, $expected_term_reference_tid = NULL) {
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
   * Tests the Drupal 7 i18n taxonomy term to Drupal 8 migration.
   */
  public function testTranslatedTaxonomyTerms() {
    $this->assertEntity(19, 'und', 'Jupiter Station', 'vocablocalized', 'Holographic research.', 'filtered_html', '0', []);
    $this->assertEntity(20, 'und', 'DS9', 'vocablocalized', 'Terok Nor', 'filtered_html', '0', []);
    $this->assertEntity(21, 'en', 'High council', 'vocabtranslate', NULL, NULL, '0', []);
    $this->assertEntity(22, 'fr', 'fr - High council', 'vocabtranslate', NULL, NULL, '0', []);
    $this->assertEntity(23, 'is', 'is - High council', 'vocabtranslate', NULL, NULL, '0', []);
    $this->assertEntity(24, 'fr', 'FR - Crewman', 'vocabfixed', NULL, NULL, '0', []);
  }

}
