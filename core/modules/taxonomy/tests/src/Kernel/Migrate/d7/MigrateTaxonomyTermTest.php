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

    $this->migrateTaxonomyTerms();
    $this->executeMigrations([
      'language',
      'd7_user_role',
      'd7_user',
      'd7_entity_translation_settings',
      'd7_taxonomy_term_entity_translation',
    ]);
  }

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
   * @param int $expected_field_integer_value
   *   The value the migrated entity field should have.
   * @param int $expected_term_reference_tid
   *   The term reference id the migrated entity field should have.
   * @param bool $expected_container_flag
   *   The term should be a container entity.
   *
   * @internal
   */
  protected function assertEntity(int $id, string $expected_language, string $expected_label, string $expected_vid, ?string $expected_description = '', ?string $expected_format = NULL, int $expected_weight = 0, array $expected_parents = [], int $expected_field_integer_value = NULL, int $expected_term_reference_tid = NULL, bool $expected_container_flag = FALSE): void {
    /** @var \Drupal\taxonomy\TermInterface $entity */
    $entity = Term::load($id);
    $this->assertInstanceOf(TermInterface::class, $entity);
    $this->assertSame($expected_language, $entity->language()->getId());
    $this->assertEquals($expected_label, $entity->label());
    $this->assertEquals($expected_vid, $entity->bundle());
    $this->assertEquals($expected_description, $entity->getDescription());
    $this->assertEquals($expected_format, $entity->getFormat());
    $this->assertEquals($expected_weight, (int) $entity->getWeight());
    $this->assertEquals($expected_parents, $this->getParentIDs($id));
    $this->assertHierarchy($expected_vid, $id, $expected_parents);
    if (!is_null($expected_field_integer_value)) {
      $this->assertTrue($entity->hasField('field_integer'));
      $this->assertEquals($expected_field_integer_value, $entity->field_integer->value);
    }
    if (!is_null($expected_term_reference_tid)) {
      $this->assertTrue($entity->hasField('field_integer'));
      $this->assertEquals($expected_term_reference_tid, $entity->field_term_reference->target_id);
    }
    if ($entity->hasField('forum_container')) {
      $this->assertEquals($expected_container_flag, $entity->forum_container->value);
    }
  }

  /**
   * Tests the Drupal 7 taxonomy term to Drupal 8 migration.
   */
  public function testTaxonomyTerms() {
    $this->assertEntity(1, 'en', 'General discussion', 'forums', '', NULL, 2);

    // Tests that terms that used the Drupal 7 Title module and that have their
    // name and description replaced by real fields are correctly migrated.
    $this->assertEntity(2, 'en', 'Term1 (This is a real field!)', 'test_vocabulary', 'The first term. (This is a real field!)', 'filtered_html', 0, [], NULL, 3);

    $this->assertEntity(3, 'en', 'Term2', 'test_vocabulary', 'The second term.', 'filtered_html');
    $this->assertEntity(4, 'en', 'Term3 in plain old English', 'test_vocabulary', 'The third term in plain old English.', 'full_html', 0, [3], 6);
    $this->assertEntity(5, 'en', 'Custom Forum', 'forums', 'Where the cool kids are.', NULL, 3);
    $this->assertEntity(6, 'en', 'Games', 'forums', NULL, '', 4, []);
    $this->assertEntity(7, 'en', 'Minecraft', 'forums', '', NULL, 1, [6]);
    $this->assertEntity(8, 'en', 'Half Life 3', 'forums', '', NULL, 0, [6]);

    // Verify that we still can create forum containers after the migration.
    $term = Term::create(['vid' => 'forums', 'name' => 'Forum Container', 'forum_container' => 1]);
    $term->save();

    // Reset the forums tree data so this new term is included in the tree.
    unset($this->treeData['forums']);
    $this->assertEntity(26, 'en', 'Forum Container', 'forums', '', '', 0, [], NULL, NULL, 1);

    // Test taxonomy term language translations.
    $this->assertEntity(19, 'en', 'Jupiter Station', 'vocablocalized', 'Holographic research.', 'filtered_html', 0, [], NULL, NULL, 1);
    $this->assertEntity(20, 'en', 'DS9', 'vocablocalized', 'Terok Nor', 'filtered_html', 0, [], NULL, NULL, 1);
    $this->assertEntity(21, 'en', 'High council', 'vocabtranslate', NULL, NULL, 0, [], NULL, NULL, 1);
    $this->assertEntity(22, 'fr', 'fr - High council', 'vocabtranslate', NULL, NULL, 0, [], NULL, NULL, 1);
    $this->assertEntity(23, 'is', 'is - High council', 'vocabtranslate', NULL, NULL, 0, [], NULL, NULL, 1);
    $this->assertEntity(24, 'fr', 'FR - Crewman', 'vocabfixed', NULL, NULL, 0, [], NULL, NULL, 1);

    // Localized.
    $this->assertEntity(19, 'en', 'Jupiter Station', 'vocablocalized', 'Holographic research.', 'filtered_html', '0', []);
    $this->assertEntity(20, 'en', 'DS9', 'vocablocalized', 'Terok Nor', 'filtered_html', '0', []);
    $this->assertEntity(25, 'en', 'Emissary', 'vocablocalized2', 'Pilot episode', 'filtered_html', '0', []);

    /** @var \Drupal\taxonomy\TermInterface $entity */
    $entity = Term::load(20);
    $this->assertSame('Bajor', $entity->field_sector->value);

    // Translate.
    $this->assertEntity(21, 'en', 'High council', 'vocabtranslate', NULL, NULL, '0', []);
    $entity = Term::load(21);
    $this->assertSame("K'mpec", $entity->field_chancellor->value);
    $this->assertEntity(22, 'fr', 'fr - High council', 'vocabtranslate', NULL, NULL, '0', []);
    $this->assertEntity(23, 'is', 'is - High council', 'vocabtranslate', NULL, NULL, '0', []);

    // Fixed.
    $this->assertEntity(24, 'fr', 'FR - Crewman', 'vocabfixed', NULL, NULL, '0', []);

    // Tests the migration of taxonomy term entity translations.
    $manager = $this->container->get('content_translation.manager');

    // Get the term and its translations.
    $term = Term::load(4);
    $term_fr = $term->getTranslation('fr');
    $term_is = $term->getTranslation('is');

    // Test that fields translated with Entity Translation are migrated.
    $this->assertSame('Term3 in plain old English', $term->getName());
    $this->assertSame('Term3 en français s\'il vous plaît', $term_fr->getName());
    $this->assertSame('Term3 á íslensku', $term_is->getName());
    $this->assertSame('The third term in plain old English.', $term->getDescription());
    $this->assertSame('The third term en français s\'il vous plaît.', $term_fr->getDescription());
    $this->assertSame('The third term á íslensku.', $term_is->getDescription());
    $this->assertSame('full_html', $term->getFormat());
    $this->assertSame('filtered_html', $term_fr->getFormat());
    $this->assertSame('plain_text', $term_is->getFormat());
    $this->assertSame('6', $term->field_integer->value);
    $this->assertSame('5', $term_fr->field_integer->value);
    $this->assertSame('4', $term_is->field_integer->value);

    // Test that the French translation metadata is correctly migrated.
    $metadata_fr = $manager->getTranslationMetadata($term_fr);
    $this->assertTrue($metadata_fr->isPublished());
    $this->assertSame('en', $metadata_fr->getSource());
    $this->assertSame('2', $metadata_fr->getAuthor()->uid->value);
    $this->assertSame('1531922267', $metadata_fr->getCreatedTime());
    $this->assertSame('1531922268', $metadata_fr->getChangedTime());
    $this->assertTrue($metadata_fr->isOutdated());

    // Test that the Icelandic translation metadata is correctly migrated.
    $metadata_is = $manager->getTranslationMetadata($term_is);
    $this->assertFalse($metadata_is->isPublished());
    $this->assertSame('en', $metadata_is->getSource());
    $this->assertSame('1', $metadata_is->getAuthor()->uid->value);
    $this->assertSame('1531922278', $metadata_is->getCreatedTime());
    $this->assertSame('1531922279', $metadata_is->getChangedTime());
    $this->assertFalse($metadata_is->isOutdated());

    // Test that untranslatable properties are the same as the source language.
    $this->assertSame($term->bundle(), $term_fr->bundle());
    $this->assertSame($term->bundle(), $term_is->bundle());
    $this->assertSame($term->getWeight(), $term_fr->getWeight());
    $this->assertSame($term->getWeight(), $term_is->getWeight());
    $this->assertSame($term->parent->terget_id, $term_fr->parent->terget_id);
    $this->assertSame($term->parent->terget_id, $term_is->parent->terget_id);
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
    return array_keys(\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($tid));
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
