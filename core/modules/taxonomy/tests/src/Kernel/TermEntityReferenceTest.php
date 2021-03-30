<?php

namespace Drupal\Tests\taxonomy\Kernel;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests the settings of restricting term selection to a single vocabulary.
 *
 * @group taxonomy
 */
class TermEntityReferenceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field',
    'system',
    'taxonomy',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');
  }

  /**
   * Tests an entity reference field restricted to a single vocabulary.
   *
   * Creates two vocabularies with a term, then set up the entity reference
   * field to limit the target vocabulary to one of them, ensuring that
   * the restriction applies.
   */
  public function testSelectionTestVocabularyRestriction() {
    // Create two vocabularies.
    $vocabulary = Vocabulary::create([
      'name' => 'test1',
      'vid' => 'test1',
    ]);
    $vocabulary->save();
    $vocabulary2 = Vocabulary::create([
      'name' => 'test2',
      'vid' => 'test2',
    ]);
    $vocabulary2->save();

    $term = Term::create([
      'name' => 'term1',
      'vid' => $vocabulary->id(),
    ]);
    $term->save();
    $term2 = Term::create([
      'name' => 'term2',
      'vid' => $vocabulary2->id(),
    ]);
    $term2->save();

    // Create an entity reference field.
    $field_name = 'taxonomy_' . $vocabulary->id();
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'translatable' => FALSE,
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
      'type' => 'entity_reference',
      'cardinality' => 1,
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'entity_type' => 'entity_test',
      'bundle' => 'test_bundle',
      'settings' => [
        'handler' => 'default',
        'handler_settings' => [
          // Restrict selection of terms to a single vocabulary.
          'target_bundles' => [
            $vocabulary->id() => $vocabulary->id(),
          ],
        ],
      ],
    ]);
    $field->save();

    $handler = $this->container->get('plugin.manager.entity_reference_selection')->getSelectionHandler($field);
    $result = $handler->getReferenceableEntities();

    $expected_result = [
      $vocabulary->id() => [
        $term->id() => $term->getName(),
      ],
    ];

    $this->assertSame($expected_result, $result, 'Terms selection restricted to a single vocabulary.');
  }

}
