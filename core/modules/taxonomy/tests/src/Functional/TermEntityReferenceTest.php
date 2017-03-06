<?php

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Tests the settings of restricting term selection to a single vocabulary.
 *
 * @group taxonomy
 */
class TermEntityReferenceTest extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_reference_test', 'entity_test'];

  /**
   * Tests an entity reference field restricted to a single vocabulary.
   *
   * Creates two vocabularies with a term, then set up the entity reference
   * field to limit the target vocabulary to one of them, ensuring that
   * the restriction applies.
   */
  public function testSelectionTestVocabularyRestriction() {

    // Create two vocabularies.
    $vocabulary = $this->createVocabulary();
    $vocabulary2 = $this->createVocabulary();

    $term = $this->createTerm($vocabulary);
    $term2 = $this->createTerm($vocabulary2);

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

    $this->assertIdentical($result, $expected_result, 'Terms selection restricted to a single vocabulary.');
  }

}
