<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel\Migrate\d7;

use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\taxonomy\TermInterface;

/**
 * Test migration of translated taxonomy terms.
 *
 * @group migrate_drupal_7
 * @group #slow
 */
class MigrateTaxonomyTermTranslationTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'content_translation',
    'datetime',
    'datetime_range',
    'image',
    'language',
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
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('comment');
    $this->installEntitySchema('file');
    $this->installEntitySchema('taxonomy_term');
    $this->migrateFields();

    $this->executeMigrations([
      'language',
      'd7_language_content_taxonomy_vocabulary_settings',
      'd7_taxonomy_vocabulary',
      'd7_taxonomy_term',
      'd7_entity_translation_settings',
      'd7_taxonomy_term_entity_translation',
      'd7_taxonomy_term_localized_translation',
      'd7_taxonomy_term_translation',
    ]);
  }

  /**
   * Validates a migrated term contains the expected values.
   *
   * @param int $id
   *   Entity ID to load and check.
   * @param string $expected_language
   *   The language code for this term.
   * @param string $expected_label
   *   The label the migrated entity should have.
   * @param string $expected_vid
   *   The parent vocabulary the migrated entity should have.
   * @param string|null $expected_description
   *   The description the migrated entity should have.
   * @param string|null $expected_format
   *   The format the migrated entity should have.
   * @param int $expected_weight
   *   The weight the migrated entity should have.
   * @param array $expected_parents
   *   The parent terms the migrated entity should have.
   * @param int $expected_field_integer_value
   *   The value the migrated entity field should have.
   * @param int $expected_term_reference_tid
   *   The term reference ID the migrated entity field should have.
   *
   * @internal
   */
  protected function assertEntity(int $id, string $expected_language, string $expected_label, string $expected_vid, ?string $expected_description = '', ?string $expected_format = NULL, int $expected_weight = 0, array $expected_parents = [], ?int $expected_field_integer_value = NULL, ?int $expected_term_reference_tid = NULL): void {
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
   * Asserts that a term is present in the tree storage, with the right parents.
   *
   * @param string $vid
   *   Vocabulary ID.
   * @param int $tid
   *   ID of the term to check.
   * @param array $parent_ids
   *   The expected parent term IDs.
   *
   * @internal
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
    $this->assertEquals($parent_ids, array_filter($term->parents), "Term $tid has correct parents in taxonomy tree");
  }

  /**
   * Tests the Drupal i18n  taxonomy term to Drupal 8 migration.
   */
  public function testTaxonomyTermTranslation(): void {
    // Forums vocabulary, no multilingual option.
    $this->assertEntity(1, 'en', 'General discussion', 'sujet_de_discussion', NULL, NULL, 2, []);
    $this->assertEntity(5, 'en', 'Custom Forum', 'sujet_de_discussion', 'Where the cool kids are.', NULL, 3, []);
    $this->assertEntity(6, 'en', 'Games', 'sujet_de_discussion', NULL, NULL, 4, []);
    $this->assertEntity(7, 'en', 'Minecraft', 'sujet_de_discussion', NULL, NULL, 1, ['6']);
    $this->assertEntity(8, 'en', 'Half Life 3', 'sujet_de_discussion', NULL, NULL, 0, ['6']);

    // Test vocabulary, field translation.
    $this->assertEntity(2, 'en', 'Term1 (This is a real field!)', 'test_vocabulary', 'The first term. (This is a real field!)', 'filtered_html', 0, []);
    $this->assertEntity(3, 'en', 'Term2', 'test_vocabulary', 'The second term.', 'filtered_html', 0, []);
    $this->assertEntity(4, 'en', 'Term3 in plain old English', 'test_vocabulary', 'The third term in plain old English.', 'full_html', 0, ['3']);

    // Tags vocabulary, no multilingual option.
    $this->assertEntity(9, 'en', 'Benjamin Sisko', 'tags', 'Portrayed by Avery Brooks', 'filtered_html', 0, []);
    $this->assertEntity(10, 'en', 'Kira Nerys', 'tags', 'Portrayed by Nana Visitor', 'filtered_html', 0, []);
    $this->assertEntity(11, 'en', 'Dax', 'tags', 'Portrayed by Terry Farrell', 'filtered_html', 0, []);
    $this->assertEntity(12, 'en', 'Jake Sisko', 'tags', 'Portrayed by Cirroc Lofton', 'filtered_html', 0, []);
    $this->assertEntity(13, 'en', 'Gul Dukat', 'tags', 'Portrayed by Marc Alaimo', 'filtered_html', 0, []);
    $this->assertEntity(14, 'en', 'Odo', 'tags', 'Portrayed by Rene Auberjonois', 'filtered_html', 0, []);
    $this->assertEntity(15, 'en', 'Worf', 'tags', 'Portrayed by Michael Dorn', 'filtered_html', 0, []);
    $this->assertEntity(16, 'en', "Miles O'Brien", 'tags', 'Portrayed by Colm Meaney', 'filtered_html', 0, []);
    $this->assertEntity(17, 'en', 'Quark', 'tags', 'Portrayed by Armin Shimerman', 'filtered_html', 0, []);
    $this->assertEntity(18, 'en', 'Elim Garak', 'tags', 'Portrayed by Andrew Robinson', 'filtered_html', 0, []);

    // Localized.
    $this->assertEntity(19, 'en', 'Jupiter Station', 'vocablocalized', 'Holographic research.', 'filtered_html', 0, []);
    $this->assertEntity(20, 'en', 'DS9', 'vocablocalized', 'Terok Nor', 'filtered_html', 0, []);
    /** @var \Drupal\taxonomy\TermInterface $entity */
    $entity = Term::load(20);
    $this->assertSame('Bajor', $entity->field_sector->value);

    // Translate.
    $this->assertEntity(21, 'en', 'High council', 'vocabtranslate', NULL, NULL, 0, []);
    $entity = Term::load(21);
    $this->assertSame("K'mpec", $entity->field_chancellor->value);

    $this->assertEntity(22, 'fr', 'fr - High council', 'vocabtranslate', NULL, NULL, 0, []);
    $entity = Term::load(22);
    $this->assertSame("fr - K'mpec", $entity->field_chancellor->value);
    $this->assertEntity(23, 'is', 'is - High council', 'vocabtranslate', NULL, NULL, 0, []);

    // Fixed.
    $this->assertEntity(24, 'fr', 'FR - Crewman', 'vocabfixed', NULL, NULL, 0, []);
    $entity = Term::load(24);
    $this->assertSame('fr - specialist', $entity->field_training->value);
  }

}
