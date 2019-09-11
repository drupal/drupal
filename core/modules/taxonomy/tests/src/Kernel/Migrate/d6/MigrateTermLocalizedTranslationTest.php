<?php

namespace Drupal\Tests\taxonomy\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;

/**
 * Tests migration of localized translated taxonomy terms.
 *
 * @group migrate_drupal_6
 */
class MigrateTermLocalizedTranslationTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'content_translation',
    'language',
    'menu_ui',
    'node',
    'taxonomy',
    // Required for translation migrations.
    'migrate_drupal_multilingual',
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
      'language',
      'd6_node_type',
      'd6_field',
      'd6_taxonomy_vocabulary',
      'd6_field_instance',
      'd6_taxonomy_term',
      'd6_taxonomy_term_localized_translation',
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
   * Asserts that a term is present in the tree storage, with the right parents.
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
    $this->assertEquals($parent_ids, array_filter($term->parents), "Term $tid has correct parents in taxonomy tree");
  }

  /**
   * Tests the Drupal 6 i18n localized taxonomy term to Drupal 8 migration.
   */
  public function testTranslatedLocalizedTaxonomyTerms() {
    $this->assertEntity(14, 'en', 'Talos IV', 'vocabulary_name_much_longer_th', 'The home of Captain Christopher Pike.', NULL, '0', []);
    $this->assertEntity(15, 'en', 'Vulcan', 'vocabulary_name_much_longer_th', NULL, NULL, '0', []);

    /** @var \Drupal\taxonomy\TermInterface $entity */
    $entity = Term::load(14);
    $this->assertTrue($entity->hasTranslation('fr'));
    $translation = $entity->getTranslation('fr');
    $this->assertSame('fr - Talos IV', $translation->label());
    $this->assertSame('fr - The home of Captain Christopher Pike.', $translation->getDescription());

    $this->assertTrue($entity->hasTranslation('zu'));
    $translation = $entity->getTranslation('zu');
    $this->assertSame('Talos IV', $translation->label());
    $this->assertSame('zu - The home of Captain Christopher Pike.', $translation->getDescription());

    $entity = Term::load(15);
    $this->assertFalse($entity->hasTranslation('fr'));
    $this->assertTrue($entity->hasTranslation('zu'));
    $translation = $entity->getTranslation('zu');
    $this->assertSame('zu - Vulcan', $translation->label());
    $this->assertSame('', $translation->getDescription());
  }

}
