<?php

namespace Drupal\Tests\taxonomy\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Tests loading, saving and deleting vocabularies.
 *
 * @group taxonomy
 */
class VocabularyCrudTest extends KernelTestBase {

  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'filter',
    'system',
    'taxonomy',
    'taxonomy_crud',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('taxonomy_term');
  }

  /**
   * Tests deleting a taxonomy that contains terms.
   */
  public function testTaxonomyVocabularyDeleteWithTerms() {
    $vocabulary = $this->createVocabulary();
    $query = \Drupal::entityQuery('taxonomy_term')->accessCheck(FALSE)->count();

    // Assert that there are no terms left.
    $this->assertEquals(0, $query->execute());

    $terms = [];
    for ($i = 0; $i < 5; $i++) {
      $terms[$i] = $this->createTerm($vocabulary);
    }

    // Set up hierarchy. term 2 is a child of 1 and 4 a child of 1 and 2.
    $terms[2]->parent = [$terms[1]->id()];
    $terms[2]->save();
    $terms[4]->parent = [$terms[1]->id(), $terms[2]->id()];
    $terms[4]->save();

    // Assert that there are now 5 terms.
    $this->assertEquals(5, $query->execute());

    $vocabulary->delete();

    // Assert that there are no terms left.
    $this->assertEquals(0, $query->execute());
  }

  /**
   * Tests for loading multiple vocabularies.
   */
  public function testTaxonomyVocabularyLoadMultiple() {
    // Ensure there are no vocabularies.
    $this->assertEmpty(Vocabulary::loadMultiple());

    // Create some vocabularies and assign weights.
    $vocabulary1 = $this->createVocabulary();
    $vocabulary1->set('weight', 0);
    $vocabulary1->save();
    $vocabulary2 = $this->createVocabulary();
    $vocabulary2->set('weight', 1);
    $vocabulary2->save();
    $vocabulary3 = $this->createVocabulary();
    $vocabulary3->set('weight', 2);
    $vocabulary3->save();

    // Check if third party settings exist.
    $this->assertEquals('bar', $vocabulary1->getThirdPartySetting('taxonomy_crud', 'foo'));
    $this->assertEquals('bar', $vocabulary2->getThirdPartySetting('taxonomy_crud', 'foo'));
    $this->assertEquals('bar', $vocabulary3->getThirdPartySetting('taxonomy_crud', 'foo'));

    // Fetch the names for all vocabularies, confirm that they are keyed by
    // machine name.
    $names = taxonomy_vocabulary_get_names();
    $this->assertEquals($vocabulary1->id(), $names[$vocabulary1->id()]);

    // Fetch the vocabularies with Vocabulary::loadMultiple(), specifying IDs.
    // Ensure they are returned in the same order as the original array.
    $vocabularies = Vocabulary::loadMultiple([
      $vocabulary3->id(),
      $vocabulary2->id(),
      $vocabulary1->id(),
    ]);
    $loaded_order = array_keys($vocabularies);
    $expected_order = [
      $vocabulary3->id(),
      $vocabulary2->id(),
      $vocabulary1->id(),
    ];
    $this->assertSame($expected_order, $loaded_order);

    // Test loading vocabularies by their properties.
    $storage = $this->container->get('entity_type.manager')->getStorage('taxonomy_vocabulary');
    // Fetch vocabulary 1 by name.
    $vocabulary = current($storage->loadByProperties(['name' => $vocabulary1->label()]));
    $this->assertEquals($vocabulary1->id(), $vocabulary->id());

    // Fetch vocabulary 2 by name and ID.
    $vocabulary = current($storage->loadByProperties([
      'name' => $vocabulary2->label(),
      'vid' => $vocabulary2->id(),
    ]));
    $this->assertEquals($vocabulary2->id(), $vocabulary->id());
  }

  /**
   * Tests uninstall and reinstall of the taxonomy module.
   */
  public function testUninstallReinstall() {
    $vocabulary = $this->createVocabulary();
    // Field storages and fields attached to taxonomy term bundles should be
    // removed when the module is uninstalled.
    $field_name = mb_strtolower($this->randomMachineName() . '_field_name');
    $storage_definition = [
      'field_name' => $field_name,
      'entity_type' => 'taxonomy_term',
      'type' => 'text',
      'cardinality' => 4,
    ];
    FieldStorageConfig::create($storage_definition)->save();
    $field_definition = [
      'field_name' => $field_name,
      'entity_type' => 'taxonomy_term',
      'bundle' => $vocabulary->id(),
      'label' => $this->randomMachineName() . '_label',
    ];
    FieldConfig::create($field_definition)->save();

    // Remove the third party setting from the memory copy of the vocabulary.
    // We keep this invalid copy around while the taxonomy module is not even
    // installed for testing below.
    $vocabulary->unsetThirdPartySetting('taxonomy_crud', 'foo');

    $this->container->get('module_installer')->uninstall(['taxonomy']);
    $this->container->get('module_installer')->install(['taxonomy']);

    // Now create a vocabulary with the same name. All fields connected to this
    // vocabulary name should have been removed when the module was uninstalled.
    // Creating a new field with the same name and an instance of this field on
    // the same bundle name should be successful.
    $vocabulary->enforceIsNew()->save();
    FieldStorageConfig::create($storage_definition)->save();
    FieldConfig::create($field_definition)->save();
  }

}
